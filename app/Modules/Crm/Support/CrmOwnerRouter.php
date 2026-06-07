<?php

namespace App\Modules\Crm\Support;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CrmOwnerRouter
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $settings
     */
    public function resolveOwnerId(array $payload, array $settings): ?int
    {
        $rules = $this->rulesFromSettings($settings);
        if ($rules->isEmpty()) {
            return null;
        }

        foreach ($rules as $rule) {
            if ($this->matchesRule($payload, $rule)) {
                return $rule['owner_user_id'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return Collection<int, array{keyword:string, owner_user_id:int, field:?string}>
     */
    private function rulesFromSettings(array $settings): Collection
    {
        $rows = (array) ($settings['owner_routing_rules'] ?? []);

        return collect($rows)
            ->filter(fn ($row) => is_array($row) && !empty($row['keyword']) && !empty($row['owner_user_id']))
            ->map(function (array $row): ?array {
                $ownerId = User::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->whereKey((int) $row['owner_user_id'])
                    ->value('id');

                if (!$ownerId) {
                    return null;
                }

                $rawKeyword = trim((string) $row['keyword']);
                $field = null;
                $keyword = $rawKeyword;

                if (str_contains($rawKeyword, ':')) {
                    [$candidateField, $candidateKeyword] = array_map('trim', explode(':', $rawKeyword, 2));
                    if (in_array(Str::lower($candidateField), ['source', 'provider', 'campaign', 'adset', 'form', 'title'], true) && $candidateKeyword !== '') {
                        $field = Str::lower($candidateField);
                        $keyword = $candidateKeyword;
                    }
                }

                return [
                    'keyword' => Str::lower($keyword),
                    'field' => $field,
                    'owner_user_id' => (int) $ownerId,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array{keyword:string, owner_user_id:int, field:?string}  $rule
     */
    private function matchesRule(array $payload, array $rule): bool
    {
        $haystacks = $rule['field']
            ? collect([$this->valueForField($payload, $rule['field'])])
            : collect([
                $this->valueForField($payload, 'source'),
                $this->valueForField($payload, 'provider'),
                $this->valueForField($payload, 'campaign'),
                $this->valueForField($payload, 'adset'),
                $this->valueForField($payload, 'form'),
                $this->valueForField($payload, 'title'),
            ]);

        return $haystacks
            ->map(fn (?string $value) => Str::lower(trim((string) $value)))
            ->filter()
            ->contains(fn (string $haystack) => str_contains($haystack, $rule['keyword']));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function valueForField(array $payload, string $field): ?string
    {
        return match ($field) {
            'source' => (string) ($payload['lead_source'] ?? ''),
            'provider' => (string) ($payload['provider'] ?? ''),
            'campaign' => (string) ($payload['campaign_name'] ?? ''),
            'adset' => (string) ($payload['adset_name'] ?? ''),
            'form' => (string) ($payload['form_name'] ?? ''),
            'title' => (string) ($payload['title'] ?? ''),
            default => null,
        };
    }
}
