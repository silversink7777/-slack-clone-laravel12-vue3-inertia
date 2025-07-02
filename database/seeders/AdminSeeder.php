<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // スーパー管理者を作成
        Admin::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // 一般管理者を作成
        Admin::create([
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // モデレーターを作成
        Admin::create([
            'name' => 'Moderator',
            'email' => 'moderator@example.com',
            'password' => Hash::make('password'),
            'role' => 'moderator',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('管理者アカウントが作成されました。');
        $this->command->info('Super Admin: admin@example.com / password');
        $this->command->info('Admin: admin2@example.com / password');
        $this->command->info('Moderator: moderator@example.com / password');
    }
}
