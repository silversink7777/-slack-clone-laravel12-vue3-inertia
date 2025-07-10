<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\PinnedMessage;
use App\Models\User;
use App\Models\Channel;

class CreateTestPinnedMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-pinned-message {channel_id} {message_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test pinned message for testing purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $channelId = $this->argument('channel_id');
        $messageId = $this->argument('message_id');

        // チャンネルの存在確認
        $channel = Channel::find($channelId);
        if (!$channel) {
            $this->error("Channel with ID {$channelId} not found.");
            return 1;
        }

        // メッセージIDが指定されていない場合、チャンネルの最新メッセージを使用
        if (!$messageId) {
            $message = Message::where('channel_id', $channelId)
                ->latest()
                ->first();
            
            if (!$message) {
                $this->error("No messages found in channel {$channelId}.");
                return 1;
            }
            
            $messageId = $message->id;
        } else {
            // 指定されたメッセージの存在確認
            $message = Message::find($messageId);
            if (!$message) {
                $this->error("Message with ID {$messageId} not found.");
                return 1;
            }
            
            if ($message->channel_id != $channelId) {
                $this->error("Message {$messageId} does not belong to channel {$channelId}.");
                return 1;
            }
        }

        // 既にピン留めされているかチェック
        $existingPin = PinnedMessage::where('message_id', $messageId)
            ->where('channel_id', $channelId)
            ->first();
            
        if ($existingPin) {
            $this->warn("Message {$messageId} is already pinned in channel {$channelId}.");
            return 0;
        }

        // テストユーザーを取得（最初のユーザーを使用）
        $user = User::first();
        if (!$user) {
            $this->error("No users found. Please create a user first.");
            return 1;
        }

        // ピン留めを作成
        $pinnedMessage = PinnedMessage::create([
            'message_id' => $messageId,
            'channel_id' => $channelId,
            'pinned_by' => $user->id,
        ]);

        $this->info("Successfully pinned message {$messageId} in channel {$channelId}.");
        $this->info("Pinned by: {$user->name}");
        $this->info("Pinned at: {$pinnedMessage->created_at}");

        return 0;
    }
}
