<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelMember;
use App\Models\User;
use Illuminate\Console\Command;

class AddAdminToChannels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:admin-to-channels {user_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add admin role to existing channels for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');
        
        if (!$userId) {
            // 最初のユーザーを取得
            $user = User::first();
            if (!$user) {
                $this->error('No users found in the database');
                return 1;
            }
            $userId = $user->id;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found");
            return 1;
        }

        $this->info("Adding admin role for user: {$user->name} (ID: {$user->id})");

        $channels = Channel::all();
        $addedCount = 0;

        foreach ($channels as $channel) {
            // 既にメンバーとして登録されているかチェック
            $existingMember = ChannelMember::where('channel_id', $channel->id)
                ->where('user_id', $userId)
                ->first();

            if ($existingMember) {
                $this->line("User already a member of channel '{$channel->name}' with role: {$existingMember->role}");
                continue;
            }

            // 管理者として追加
            ChannelMember::create([
                'channel_id' => $channel->id,
                'user_id' => $userId,
                'role' => 'admin',
            ]);

            $this->info("Added admin role to channel: {$channel->name}");
            $addedCount++;
        }

        $this->info("\nCompleted! Added admin role to {$addedCount} channels.");
    }
} 