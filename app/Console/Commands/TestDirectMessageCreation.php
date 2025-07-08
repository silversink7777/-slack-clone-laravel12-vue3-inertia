<?php

namespace App\Console\Commands;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Console\Command;

class TestDirectMessageCreation extends Command
{
    protected $signature = 'test:create-dm-manual';
    protected $description = 'Test DirectMessage creation manually';

    public function handle()
    {
        $this->info('Testing DirectMessage creation...');
        
        // ユーザーを取得
        $user1 = User::find(8);
        $user2 = User::find(9);
        
        if (!$user1 || !$user2) {
            $this->error('Users not found');
            return 1;
        }
        
        $this->info("User 1: {$user1->name} (ID: {$user1->id})");
        $this->info("User 2: {$user2->name} (ID: {$user2->id})");
        
        try {
            // DMを作成
            $directMessage = DirectMessage::create([
                'sender_id' => $user1->id,
                'receiver_id' => $user2->id,
                'content' => 'Test message from command line',
            ]);
            
            $this->info("DM created successfully! ID: {$directMessage->id}");
            $this->info("Content: {$directMessage->content}");
            $this->info("Created at: {$directMessage->created_at}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Failed to create DM: {$e->getMessage()}");
            $this->error("Trace: {$e->getTraceAsString()}");
            return 1;
        }
    }
} 