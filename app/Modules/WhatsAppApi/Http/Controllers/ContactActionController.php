<?php

namespace App\Modules\WhatsAppApi\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Conversations\Models\Conversation;
use App\Modules\Conversations\Models\ConversationMessage;
use App\Modules\WhatsAppApi\Http\Requests\SendTemplateToContactRequest;
use App\Modules\WhatsAppApi\Jobs\SendWhatsAppMessage;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\Support\TemplateVariableResolver;
use App\Support\BooleanQuery;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContactActionController extends Controller
{

    public function sendTemplate(SendTemplateToContactRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (!WATemplate::query()->where('tenant_id', $this->tenantId())->find((int) $data['template_id'])) {
            throw ValidationException::withMessages([
                'template_id' => 'Template tidak tersedia untuk tenant aktif.',
            ]);
        }

        $contact = Contact::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail((int) $data['contact_id']);
        $instance = $this->resolveInstance((int) $data['instance_id'], $request->user());
        $template = WATemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->findOrFail((int) $data['template_id']);

        if ((string) $template->status !== 'approved') {
            throw ValidationException::withMessages([
                'template_id' => 'Template harus berstatus approved.',
            ]);
        }

        if ($template->namespace && $instance->cloud_business_account_id && $template->namespace !== $instance->cloud_business_account_id) {
            throw ValidationException::withMessages([
                'template_id' => 'Template bukan milik WABA instance terpilih.',
            ]);
        }

        $phone = $contact->whatsappPhoneNumber();
        if ($phone === null) {
            throw ValidationException::withMessages([
                'contact_id' => 'Contact tidak memiliki nomor WhatsApp yang valid.',
            ]);
        }

        $variables = TemplateVariableResolver::resolveForContact(
            $template,
            $contact->loadMissing('company'),
            $request->user(),
            (array) ($data['variables'] ?? [])
        );
        $payload = $this->buildTemplatePayload($template, $variables);
        $messageBody = $this->renderTemplateText($template->body, $variables);

        foreach ($payload['placeholders'] as $idx) {
            if (!isset($variables[$idx]) || trim((string) $variables[$idx]) === '') {
                throw ValidationException::withMessages([
                    'variables' => "Nilai untuk placeholder {{$idx}} kosong. Cek mapping template atau isi override manual.",
                ]);
            }
        }

        DB::transaction(function () use ($contact, $instance, $template, $phone, $payload, $messageBody, $request): void {
            $conversation = Conversation::query()->firstOrCreate(
                [
                    'tenant_id' => $this->tenantId(),
                    'channel' => 'wa_api',
                    'instance_id' => $instance->id,
                    'contact_external_id' => $phone,
                ],
                [
                    'contact_name' => $contact->name,
                    'status' => 'open',
                    'owner_id' => $request->user()->id,
                    'claimed_at' => now(),
                    'locked_until' => now()->addMinutes((int) config('conversations.lock_minutes', 30)),
                    'last_message_at' => now(),
                    'last_outgoing_at' => now(),
                    'unread_count' => 0,
                    'metadata' => [
                        'source' => 'contacts_whatsapp_api',
                        'contact_id' => $contact->id,
                    ],
                ]
            );

            $conversation->fill([
                'contact_name' => $contact->name,
                'last_message_at' => now(),
                'last_outgoing_at' => now(),
            ])->save();

            $message = ConversationMessage::query()->create([
                'tenant_id' => $this->tenantId(),
                'conversation_id' => $conversation->id,
                'user_id' => $request->user()->id,
                'direction' => 'out',
                'type' => 'template',
                'body' => $messageBody,
                'status' => 'queued',
                'payload' => [
                    'name' => $template->name,
                    'meta_name' => method_exists($template, 'metaTemplateName') ? $template->metaTemplateName() : ($template->meta_name ?: $template->name),
                    'language' => $template->language,
                    'components' => $payload['components'],
                    'variables' => $payload['params'],
                    'template_id' => $template->id,
                    'source' => 'contacts_action',
                    'contact_id' => $contact->id,
                ],
            ]);

            SendWhatsAppMessage::dispatch($message->id);
        });

        $target = $data['return_to'] ?? route('contacts.show', $contact);

        return redirect()->to($target)->with('status', 'Template WhatsApp diantrikan.');
    }

    public static function modalData(?\Illuminate\Contracts\Auth\Authenticatable $user): array
    {
        if (!$user || !class_exists(Contact::class) || !Schema::hasTable('whatsapp_instances') || !Schema::hasTable('wa_templates')) {
            return [
                'instances' => [],
                'templates' => [],
                'contactFieldOptions' => TemplateVariableResolver::contactFieldOptions(),
                'senderFieldOptions' => TemplateVariableResolver::senderFieldOptions(),
                'senderContext' => TemplateVariableResolver::contextFromSender($user instanceof \App\Models\User ? $user : null),
            ];
        }

        $instanceQuery = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->orderBy('name');
        BooleanQuery::apply($instanceQuery, 'is_active', true);

        if (method_exists($user, 'hasRole') && !$user->hasRole('Super-admin') && Schema::hasTable('whatsapp_instance_user')) {
            $instanceQuery->whereHas('users', fn ($query) => $query->where('user_id', $user->id));
        }

        $instances = $instanceQuery->get(['id', 'name', 'provider', 'cloud_business_account_id'])
            ->map(fn (WhatsAppInstance $instance) => [
                'id' => $instance->id,
                'name' => $instance->name,
                'provider' => $instance->provider,
                'namespace' => $instance->cloud_business_account_id,
            ])
            ->values()
            ->all();

        $templates = WATemplate::query()
            ->where('tenant_id', $this->tenantId())
            ->where('status', 'approved')
            ->orderBy('name')
            ->get(['id', 'name', 'meta_name', 'language', 'namespace', 'body', 'components', 'variable_mappings'])
            ->map(fn (WATemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'meta_name' => $template->meta_name,
                'language' => $template->language,
                'namespace' => $template->namespace,
                'body' => (string) $template->body,
                'components' => $template->components ?? [],
                'variable_mappings' => $template->variable_mappings ?? [],
                'placeholders' => self::placeholderIndexes($template->body, $template->components ?? []),
            ])
            ->values()
            ->all();

        $contactFieldOptions = TemplateVariableResolver::contactFieldOptions();
        $senderFieldOptions = TemplateVariableResolver::senderFieldOptions();
        $senderContext = TemplateVariableResolver::contextFromSender($user instanceof \App\Models\User ? $user : null);

        return compact('instances', 'templates', 'contactFieldOptions', 'senderFieldOptions', 'senderContext');
    }

    private function resolveInstance(int $instanceId, $user): WhatsAppInstance
    {
        $query = WhatsAppInstance::query()
            ->where('tenant_id', $this->tenantId())
            ->where('id', $instanceId);
        BooleanQuery::apply($query, 'is_active', true);

        if (method_exists($user, 'hasRole') && !$user->hasRole('Super-admin') && Schema::hasTable('whatsapp_instance_user')) {
            $query->whereHas('users', fn ($builder) => $builder->where('user_id', $user->id));
        }

        return $query->firstOrFail();
    }

    private function buildTemplatePayload(WATemplate $template, array $params): array
    {
        $componentsSource = collect($template->components ?? []);
        $header = $componentsSource->first(function ($component) {
            return strtolower((string) data_get($component, 'type', '')) === 'header';
        });
        $headerText = null;
        $headerFormat = '';

        if (is_array($header)) {
            $headerText = data_get($header, 'text') ?: data_get($header, 'parameters.0.text');
            $headerFormat = strtolower((string) data_get($header, 'format', data_get($header, 'parameters.0.type', '')));
        }

        $bodyIndexes = self::placeholderIndexes((string) $template->body);
        $headerIndexes = self::placeholderIndexes((string) $headerText);
        $allIndexes = array_values(array_unique(array_merge($bodyIndexes, $headerIndexes)));
        sort($allIndexes);

        $components = [];
        $headerParams = [];

        if ($headerText) {
            foreach ($headerIndexes as $idx) {
                $headerParams[] = ['type' => 'text', 'text' => (string) ($params[$idx] ?? '')];
            }
        } elseif (is_array($header)) {
            $mediaType = in_array($headerFormat, ['image', 'video', 'document'], true) ? $headerFormat : 'image';
            $mediaLink = (string) (
                data_get($header, "parameters.0.{$mediaType}.link")
                ?: data_get($header, 'parameters.0.link')
                ?: data_get($header, 'example.header_url.0')
            );

            if ($mediaLink !== '') {
            $headerParams[] = [
                'type' => $mediaType,
                $mediaType => [
                        'link' => $mediaLink,
                    ],
                ];
            } elseif (in_array($mediaType, ['image', 'video', 'document'], true)) {
                throw ValidationException::withMessages([
                    'template_id' => 'Template ini memakai media header, tetapi URL media header belum tersimpan di aplikasi. Buka template lalu upload/set ulang media header sebelum kirim.',
                ]);
            }
        }

        if (!empty($headerParams)) {
            $components[] = ['type' => 'header', 'parameters' => $headerParams];
        }

        $bodyParams = [];
        foreach ($bodyIndexes as $idx) {
            $bodyParams[] = ['type' => 'text', 'text' => (string) ($params[$idx] ?? '')];
        }

        if (!empty($bodyParams)) {
            $components[] = ['type' => 'body', 'parameters' => $bodyParams];
        }

        return [
            'components' => $components,
            'params' => $params,
            'placeholders' => $allIndexes,
        ];
    }

    private function renderTemplateText(string $text, array $params): string
    {
        return (string) preg_replace_callback('/\{\{(\d+)\}\}/', function (array $matches) use ($params): string {
            $index = (int) ($matches[1] ?? 0);
            return (string) ($params[$index] ?? '');
        }, $text);
    }

    public static function placeholderIndexes(?string $body, array $components = []): array
    {
        $indexes = self::extractPlaceholderIndexes((string) $body);

        foreach ($components as $component) {
            if (!is_array($component) || strtolower((string) ($component['type'] ?? '')) !== 'header') {
                continue;
            }

            $headerText = (string) (($component['text'] ?? '') ?: data_get($component, 'parameters.0.text', ''));
            $indexes = array_merge($indexes, self::extractPlaceholderIndexes($headerText));
        }

        $indexes = array_values(array_unique(array_map('intval', $indexes)));
        sort($indexes);

        return $indexes;
    }

    private static function extractPlaceholderIndexes(?string $text): array
    {
        if (!$text) {
            return [];
        }

        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        return array_map('intval', $matches[1] ?? []);
    }

    private function tenantId(): int
    {
        return TenantContext::currentId();
    }
}
