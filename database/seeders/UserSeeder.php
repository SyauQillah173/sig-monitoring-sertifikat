<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_USER_PASSWORD', 'Password123!');

        $users = [
            [
                'name' => 'Admin SIG',
                'legacy_emails' => ['admin@sig.test', 'abdulahsyauqilah03@gmail.com'],
                'email' => 'abdullahsyauqillah03@gmail.com',
                'role' => UserRole::Admin,
                'is_super_admin' => true,
                'has_custom_access' => false,
            ],
            [
                'name' => 'Petugas SIG',
                'legacy_emails' => ['petugas@sig.test', 'abdulahsyauqilah012@gmail.com'],
                'email' => 'abdullahsyauqillah012@gmail.com',
                'role' => UserRole::Petugas,
                'is_super_admin' => false,
                'has_custom_access' => false,
            ],
        ];

        foreach ($users as $account) {
            $user = User::query()
                ->whereIn('email', [$account['email'], ...$account['legacy_emails']])
                ->first() ?? new User;

            $user->forceFill([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => Hash::make($password),
                'role' => $account['role'],
                'is_super_admin' => $account['is_super_admin'],
                'has_custom_access' => $account['has_custom_access'],
                'email_verified_at' => now(),
            ])->save();

            $user->assignAppRole($account['role']);
        }
    }
}
