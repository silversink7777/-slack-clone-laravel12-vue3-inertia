<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test message for development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 最初のユーザーとチャンネルを取得
        $user = User::first();
        $channel = Channel::first();

        if (!$user) {
            $this->error('No users found in database');
            return;
        }

        if (!$channel) {
            $this->error('No channels found in database');
            return;
        }

        $message = Message::create([
            'content' => 'Hello, this is a test message!',
            'user_id' => $user->id,
            'channel_id' => $channel->id,
        ]);

        $this->info("Test message created successfully with ID: {$message->id}");
        $this->info("Message: {$message->content}");
        $this->info("User: {$user->name}");
        $this->info("Channel: {$channel->name}");
        
        // 既存のメッセージ数も表示
        $messageCount = Message::count();
        $this->info("Total messages in database: {$messageCount}");
    }
} 