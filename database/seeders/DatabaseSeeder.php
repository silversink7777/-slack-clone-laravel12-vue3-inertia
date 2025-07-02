<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 管理者アカウントを作成
        $this->call([
            AdminSeeder::class,
        ]);

        User::factory(10)->create();

        // 固定のチャンネルを作成
        Channel::factory()->create(['name' => 'all-test']);
        Channel::factory()->create(['name' => 'test']);
        Channel::factory()->create(['name' => 'ソーシャル']);
        // ランダムなチャンネルを2つ作成
        Channel::factory(2)->create();

        Message::factory(30)->create();
    }
}
