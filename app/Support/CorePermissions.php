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
    ];

    public const DEFAULT_ROLE_PERMISSIONS = [
        'Super-admin' => self::PERMISSIONS,
        'Admin' => [],
    ];
}
