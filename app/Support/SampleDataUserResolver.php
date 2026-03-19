<?php

namespace App\Support;

use App\Models\User;

class SampleDataUserResolver
{
    public static function resolve(): ?User
    {
        return User::query()
            ->whereHas('roles', function ($query): void {
                $query->where('name', 'Super-admin');
            })
            ->orderBy('id')
            ->first()
            ?? User::query()->orderBy('id')->first();
    }
}
