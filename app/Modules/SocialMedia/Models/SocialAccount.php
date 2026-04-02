<?php

namespace App\Modules\SocialMedia\Models;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'platform',
        'page_id',
        'ig_business_id',
        'access_token',
        'access_token_hash',
        'name',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $hidden = [
        'access_token_hash',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $account): void {
            if (!$account->tenant_id) {
                $account->tenant_id = TenantContext::currentId();
            }
        });
    }

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptSecret($value),
            set: function ($value): array {
                $token = trim((string) $value);

                if ($token === '') {
                    return [
                        'access_token' => null,
                        'access_token_hash' => null,
                    ];
                }

                return [
                    'access_token' => $this->encryptSecret($token),
                    'access_token_hash' => hash('sha256', $token),
                ];
            },
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chatbotIntegration(): HasOne
    {
        return $this->hasOne(SocialAccountChatbotIntegration::class, 'social_account_id');
    }

    public function updateOperationalMetadata(array $attributes): void
    {
        $metadata = is_array($this->metadata) ? $this->metadata : [];

        foreach ($attributes as $key => $value) {
            if ($value === null) {
                unset($metadata[$key]);
                continue;
            }

            $metadata[$key] = $value;
        }

        $this->forceFill(['metadata' => $metadata])->save();
    }

    public function lastInboundAt(): ?Carbon
    {
        $value = data_get($this->metadata, 'last_inbound_at');

        return filled($value) ? Carbon::parse((string) $value) : null;
    }

    public function lastOutboundAt(): ?Carbon
    {
        $value = data_get($this->metadata, 'last_outbound_at');

        return filled($value) ? Carbon::parse((string) $value) : null;
    }

    public function lastOutboundErrorAt(): ?Carbon
    {
        $value = data_get($this->metadata, 'last_outbound_error_at');

        return filled($value) ? Carbon::parse((string) $value) : null;
    }

    public function lastOutboundErrorMessage(): ?string
    {
        $value = trim((string) data_get($this->metadata, 'last_outbound_error_message', ''));

        return $value !== '' ? $value : null;
    }

    public function lastInboundSummary(): ?string
    {
        $value = trim((string) data_get($this->metadata, 'last_inbound_summary', ''));

        return $value !== '' ? $value : null;
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('tenant_id', TenantContext::currentId())
            ->firstOrFail();
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
