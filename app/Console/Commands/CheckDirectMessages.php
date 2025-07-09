<?php

namespace App\Console\Commands;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Console\Command;

class CheckDirectMessages extends Command
{
    protected $signature = 'test:check-dms';
    protected $description = 'Check direct message data';

    public function handle()
    {
        $this->info('Checking Direct Messages...');
        
        $dms = DirectMessage::with(['sender', 'receiver'])->get();
        
        if ($dms->isEmpty()) {
            $this->warn('No direct messages found.');
            return 0;
        }
        
        foreach ($dms as $dm) {
            $this->line("DM ID: {$dm->id}");
            $this->line("  From: {$dm->sender->name} (ID: {$dm->sender_id})");
            $this->line("  To: {$dm->receiver->name} (ID: {$dm->receiver_id})");
            $this->line("  Content: {$dm->content}");
            $this->line("  Created: {$dm->created_at}");
            $this->line('');
        }
        
        // ユーザー一覧も表示
        $this->info('Users:');
        $users = User::all();
        foreach ($users as $user) {
            $this->line("  ID: {$user->id}, Name: {$user->name}");
        }
        
        return 0;
    }
} 