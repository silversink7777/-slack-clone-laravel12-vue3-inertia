<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\DirectMessage;

class CreateTestDirectMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-dm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create test direct message data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 現在のユーザーID 8と9を使用
        $user1 = User::find(8);
        $user2 = User::find(9);

        if (!$user1 || !$user2) {
            $this->error('Users with ID 8 and 9 not found.');
            return 1;
        }

        // 既存のDMをチェック
        $existingDm = DirectMessage::where(function ($query) use ($user1, $user2) {
            $query->where('sender_id', $user1->id)
                  ->where('receiver_id', $user2->id);
        })->orWhere(function ($query) use ($user1, $user2) {
            $query->where('sender_id', $user2->id)
                  ->where('receiver_id', $user1->id);
        })->first();

        if ($existingDm) {
            $this->info('Direct message already exists between ' . $user1->name . ' and ' . $user2->name);
            return 0;
        }

        // 新しいDMを作成
        DirectMessage::create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'content' => 'Hello! This is a test direct message between current users.',
        ]);

        $this->info('Test direct message created between ' . $user1->name . ' and ' . $user2->name);
        return 0;
    }
}
