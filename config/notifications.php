<?php

return [
    'channel_defaults' => [
        'critical' => ['in_app', 'web_push', 'email'],
        'warning' => ['in_app', 'web_push'],
        'info' => ['in_app'],
        'success' => ['in_app'],
    ],

    'push' => [
        'vapid' => [
            'subject' => env('NOTIFICATION_VAPID_SUBJECT'),
            'public_key' => env('NOTIFICATION_VAPID_PUBLIC_KEY'),
            'private_key' => env('NOTIFICATION_VAPID_PRIVATE_KEY'),
        ],
    ],

    'polling' => [
        'topbar_seconds' => 60,
    ],
];
