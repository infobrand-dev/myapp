<?php

namespace App\Support\Notifications;

class NotificationUrlBuilder
{
    /**
     * @param  array<int, mixed>  $actions
     * @return array<int, array<string, string>>
     */
    public function normalize(array $actions): array
    {
        return collect($actions)
            ->filter(fn ($action) => is_array($action) && !empty($action['label']) && !empty($action['url']))
            ->map(fn (array $action) => [
                'label' => (string) $action['label'],
                'url' => (string) $action['url'],
            ])
            ->values()
            ->all();
    }
}
