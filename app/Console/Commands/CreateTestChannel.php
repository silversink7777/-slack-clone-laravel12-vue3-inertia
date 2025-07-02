<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelMember;
use Illuminate\Console\Command;

class CreateTestChannel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-channel {name} {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test channel for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $userId = $this->argument('user_id');

        $channel = Channel::create([
            'name' => $name,
        ]);

        // 指定されたユーザーを管理者として追加
        ChannelMember::create([
            'channel_id' => $channel->id,
            'user_id' => $userId,
            'role' => 'admin',
        ]);

        $this->info("Channel '{$name}' created successfully with ID: {$channel->id}");
        $this->info("User ID {$userId} added as admin");
    }
} 