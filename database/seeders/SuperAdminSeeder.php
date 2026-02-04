<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@myapp.test'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123!'),
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole('Super-admin');
    }
}
