<?php

namespace App\Modules\Crm\Support;

class CrmStageCatalog
{
    public const NEW_LEAD = 'new_lead';
    public const QUALIFIED = 'qualified';
    public const PROPOSAL = 'proposal';
    public const NEGOTIATION = 'negotiation';
    public const WON = 'won';
    public const LOST = 'lost';

    public static function options(): array
    {
        return [
            self::NEW_LEAD => 'New Lead',
            self::QUALIFIED => 'Qualified',
            self::PROPOSAL => 'Proposal',
            self::NEGOTIATION => 'Negotiation',
            self::WON => 'Won',
            self::LOST => 'Lost',
        ];
    }

    public static function priorities(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    public static function badgeClass(string $stage): string
    {
        return match ($stage) {
            self::NEW_LEAD => 'bg-secondary-lt text-secondary',
            self::QUALIFIED => 'bg-azure-lt text-azure',
            self::PROPOSAL => 'bg-primary-lt text-primary',
            self::NEGOTIATION => 'bg-orange-lt text-orange',
            self::WON => 'bg-green-lt text-green',
            self::LOST => 'bg-red-lt text-red',
            default => 'bg-secondary-lt text-secondary',
        };
    }

    public static function nextStage(?string $stage): ?string
    {
        $keys = array_keys(self::options());
        $index = array_search($stage, $keys, true);

        if ($index === false || $index === count($keys) - 1) {
            return null;
        }

        return $keys[$index + 1];
    }

    public static function previousStage(?string $stage): ?string
    {
        $keys = array_keys(self::options());
        $index = array_search($stage, $keys, true);

        if ($index === false || $index === 0) {
            return null;
        }

        return $keys[$index - 1];
    }
}
