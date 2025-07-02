<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\User;
use Illuminate\Console\Command;

class AddUserToChannel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:user-to-channel {user_id} {channel_id} {role=member}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a user to a specific channel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        $channelId = $this->argument('channel_id');
        $role = $this->argument('role');

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        $channel = Channel::find($channelId);
        if (!$channel) {
            $this->error("Channel with ID {$channelId} not found");
            return 1;
        }

        // 既にメンバーかどうかチェック
        $existingMember = ChannelMember::where('channel_id', $channelId)
            ->where('user_id', $userId)
            ->first();

        if ($existingMember) {
            $this->warn("User {$user->name} is already a member of channel '{$channel->name}' with role: {$existingMember->role}");
            return 0;
        }

        // メンバーとして追加
        ChannelMember::create([
            'channel_id' => $channelId,
            'user_id' => $userId,
            'role' => $role,
        ]);

        $this->info("Added user {$user->name} to channel '{$channel->name}' with role: {$role}");
    }
} 