<?php

namespace App\Modules\WhatsAppApi\Models;

use App\Models\User;
use App\Modules\Conversations\Models\Conversation;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Crypt;

class WhatsAppInstance extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_instances';

    protected $fillable = [
        'tenant_id',
        'name',
        'phone_number',
        'provider',
        'api_base_url',
        'api_token',
        'api_token_hash',
        'webhook_url',
        'status',
        'is_active',
        'settings',
        'last_health_check_at',
        'last_error',
        'created_by',
        'updated_by',
        'phone_number_id',
        'cloud_business_account_id',
        'cloud_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_health_check_at' => 'datetime',
    ];

    protected $hidden = [
        'api_token_hash',
    ];

    protected function apiToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptSecret($value),
            set: function ($value): array {
                $token = trim((string) $value);

                if ($token === '') {
                    return [
                        'api_token' => null,
                        'api_token_hash' => null,
                    ];
                }

                return [
                    'api_token' => $this->encryptSecret($token),
                    'api_token_hash' => hash('sha256', $token),
                ];
            },
        );
    }

    protected function cloudToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptSecret($value),
            set: fn ($value) => $this->normalizeSecretValue($value),
        );
    }

    protected function settings(): Attribute
    {
        return Attribute::make(
            get: function ($value): array {
                if (is_array($value)) {
                    return $this->decryptSettings($value);
                }

                $decoded = json_decode((string) $value, true);
                return $this->decryptSettings(is_array($decoded) ? $decoded : []);
            },
            set: fn ($value) => json_encode($this->encryptSettings(is_array($value) ? $value : [])),
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'whatsapp_instance_user', 'instance_id', 'user_id')
            ->withPivot(['role'])
            ->withTimestamps();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'instance_id')
            ->where('channel', 'wa_api');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function chatbotIntegration(): HasOne
    {
        return $this->hasOne(WhatsAppInstanceChatbotIntegration::class, 'instance_id');
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
    }

    private function normalizeSecretValue($value): ?string
    {
        $token = trim((string) $value);

        return $token === '' ? null : $this->encryptSecret($token);
    }

    private function encryptSettings(array $settings): array
    {
        foreach ($this->secretSettingKeys() as $key) {
            $value = trim((string) ($settings[$key] ?? ''));
            if ($value !== '') {
                $settings[$key] = $this->encryptSecret($value);
            }
        }

        return $settings;
    }

    private function decryptSettings(array $settings): array
    {
        foreach ($this->secretSettingKeys() as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            $settings[$key] = $this->decryptSecret($settings[$key]);
        }

        return $settings;
    }

    private function secretSettingKeys(): array
    {
        return [
            'wa_cloud_verify_token',
            'wa_cloud_app_secret',
        ];
    }

    private function encryptSecret(string $value): string
    {
        return str_starts_with($value, 'enc::')
            ? $value
            : 'enc::' . Crypt::encryptString($value);
    }

    private function decryptSecret($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $secret = (string) $value;
        if ($secret === '') {
            return '';
        }

        if (!str_starts_with($secret, 'enc::')) {
            return $secret;
        }

        try {
            return Crypt::decryptString(substr($secret, 5));
        } catch (\Throwable) {
            return null;
        }
    }
}
