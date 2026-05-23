<?php

namespace App\Support;

class CorePermissions
{
    public const PERMISSIONS = [
        'settings.view',
        'settings.manage',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'modules.view',
        'modules.install',
        'modules.activate',
        'modules.deactivate',
        'notifications.view',
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => ['notifications.view'],
        'Customer Service' => ['notifications.view'],
        'Sales' => ['notifications.view'],
        'Cashier' => ['notifications.view'],
        'Inventory Staff' => ['notifications.view'],
        'Finance Staff' => ['notifications.view'],
    ];
}
