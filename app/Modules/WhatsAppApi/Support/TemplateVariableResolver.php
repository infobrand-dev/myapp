<?php

namespace App\Modules\WhatsAppApi\Support;

use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\WhatsAppApi\Models\WATemplate;

class TemplateVariableResolver
{
    public static function contactFieldOptions(): array
    {
        return [
            'name' => 'Contact Name',
            'mobile' => 'Mobile',
            'phone' => 'Phone',
            'email' => 'Email',
            'company_name' => 'Company',
            'job_title' => 'Job Title',
            'website' => 'Website',
            'industry' => 'Industry',
            'city' => 'City',
            'state' => 'State',
            'country' => 'Country',
        ];
    }

    public static function senderFieldOptions(): array
    {
        return [
            'name' => 'User Name',
            'email' => 'User Email',
            'phone' => 'User Phone',
            'mobile' => 'User Mobile',
            'avatar' => 'User Avatar',
        ];
    }

    public static function placeholderIndexes(?string $body, ?string $headerText = null): array
    {
        $indexes = array_merge(
            self::extractPlaceholderIndexes((string) $body),
            self::extractPlaceholderIndexes((string) $headerText)
        );

        $indexes = array_values(array_unique(array_map('intval', $indexes)));
        sort($indexes);

        return $indexes;
    }

    public static function normalizeMappings(array $mappings, array $placeholders): array
    {
        $allowedContactFields = array_keys(self::contactFieldOptions());
        $allowedSenderFields = array_keys(self::senderFieldOptions());
        $normalized = [];

        foreach ($placeholders as $index) {
            $raw = $mappings[$index] ?? $mappings[(string) $index] ?? null;
            if (!is_array($raw)) {
                continue;
            }

            $sourceType = strtolower(trim((string) ($raw['source_type'] ?? 'text')));
            if (!in_array($sourceType, ['text', 'contact_field', 'sender_field'], true)) {
                $sourceType = 'text';
            }

            $contactField = trim((string) ($raw['contact_field'] ?? ''));
            if (!in_array($contactField, $allowedContactFields, true)) {
                $contactField = 'name';
            }

            $senderField = trim((string) ($raw['sender_field'] ?? ''));
            if (!in_array($senderField, $allowedSenderFields, true)) {
                $senderField = 'name';
            }

            $normalized[(string) $index] = [
                'source_type' => $sourceType,
                'text_value' => trim((string) ($raw['text_value'] ?? '')),
                'contact_field' => $contactField,
                'sender_field' => $senderField,
                'fallback_value' => trim((string) ($raw['fallback_value'] ?? '')),
            ];
        }

        ksort($normalized, SORT_NATURAL);

        return $normalized;
    }

    public static function resolveForContact(WATemplate $template, Contact $contact, ?User $sender = null, array $overrides = []): array
    {
        return self::resolve(
            $template,
            self::contextFromContact($contact),
            self::contextFromSender($sender),
            $overrides
        );
    }

    public static function resolve(
        WATemplate $template,
        array $contactContext = [],
        array $senderContext = [],
        array $overrides = []
    ): array {
        $placeholders = self::placeholderIndexes(
            (string) $template->body,
            self::headerText($template)
        );
        $mappings = self::normalizeMappings((array) ($template->variable_mappings ?? []), $placeholders);
        $overrides = self::normalizeOverrideVariables($overrides);

        $resolved = [];
        foreach ($placeholders as $index) {
            $override = trim((string) ($overrides[$index] ?? ''));
            if ($override !== '') {
                $resolved[$index] = $override;
                continue;
            }

            $resolved[$index] = self::resolveMappingValue(
                $mappings[(string) $index] ?? [],
                $contactContext,
                $senderContext
            );
        }

        ksort($resolved);

        return $resolved;
    }

    public static function contextFromContact(Contact $contact): array
    {
        $companyName = null;

        if ($contact->relationLoaded('parentContact')) {
            $companyName = $contact->parentContact?->name;
        } elseif ($contact->parent_contact_id) {
            $companyName = optional($contact->parentContact()->first(['name']))->name;
        }

        return self::contextFromArray([
            'name' => $contact->name,
            'mobile' => $contact->mobile,
            'phone' => $contact->phone,
            'email' => $contact->email,
            'company_name' => $companyName,
            'job_title' => $contact->job_title,
            'website' => $contact->website,
            'industry' => $contact->industry,
            'city' => $contact->city,
            'state' => $contact->state,
            'country' => $contact->country,
        ]);
    }

    public static function contextFromArray(array $context): array
    {
        $normalized = [];
        foreach (array_keys(self::contactFieldOptions()) as $field) {
            $normalized[$field] = trim((string) ($context[$field] ?? ''));
        }

        if ($normalized['mobile'] === '' && !empty($context['phone_number'])) {
            $normalized['mobile'] = trim((string) $context['phone_number']);
        }

        if ($normalized['phone'] === '' && !empty($context['phone_number'])) {
            $normalized['phone'] = trim((string) $context['phone_number']);
        }

        return $normalized;
    }

    public static function contextFromSender(?User $user): array
    {
        if (!$user) {
            return self::senderContextFromArray([]);
        }

        return self::senderContextFromArray([
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
            'phone' => $user->getAttribute('phone'),
            'mobile' => $user->getAttribute('mobile'),
            'avatar' => $user->getAttribute('avatar'),
        ]);
    }

    public static function senderContextFromArray(array $context): array
    {
        $normalized = [];
        foreach (array_keys(self::senderFieldOptions()) as $field) {
            $normalized[$field] = trim((string) ($context[$field] ?? ''));
        }

        return $normalized;
    }

    public static function normalizeOverrideVariables(array $variables): array
    {
        $normalized = [];

        foreach ($variables as $key => $value) {
            $index = (int) preg_replace('/[^0-9]/', '', (string) $key);
            if ($index <= 0) {
                continue;
            }

            $normalized[$index] = trim((string) $value);
        }

        ksort($normalized);

        return $normalized;
    }

    public static function headerText(WATemplate $template): ?string
    {
        $header = collect($template->components ?? [])->firstWhere('type', 'header');
        if (!is_array($header)) {
            return null;
        }

        return (string) (data_get($header, 'text') ?: data_get($header, 'parameters.0.text', ''));
    }

    private static function resolveMappingValue(array $mapping, array $contactContext, array $senderContext): string
    {
        $sourceType = strtolower(trim((string) ($mapping['source_type'] ?? 'text')));
        $fallback = trim((string) ($mapping['fallback_value'] ?? ''));

        if ($sourceType === 'contact_field') {
            $field = trim((string) ($mapping['contact_field'] ?? 'name'));
            $value = trim((string) ($contactContext[$field] ?? ''));
            return $value !== '' ? $value : $fallback;
        }

        if ($sourceType === 'sender_field') {
            $field = trim((string) ($mapping['sender_field'] ?? 'name'));
            $value = trim((string) ($senderContext[$field] ?? ''));
            return $value !== '' ? $value : $fallback;
        }

        $textValue = trim((string) ($mapping['text_value'] ?? ''));
        return $textValue !== '' ? $textValue : $fallback;
    }

    private static function extractPlaceholderIndexes(?string $text): array
    {
        if (!$text) {
            return [];
        }

        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);
        return array_map('intval', $matches[1] ?? []);
    }
}
