<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class TestSoftDelete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:soft-delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test soft delete functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing soft delete functionality...');
        
        // 最新のメッセージを取得
        $message = Message::with('user')->latest()->first();
        
        if (!$message) {
            $this->error('No messages found in database');
            return;
        }
        
        $this->info("Found message:");
        $this->line("- ID: {$message->id}");
        $this->line("- Content: {$message->content}");
        $this->line("- User: {$message->user->name}");
        $this->line("- Deleted at: " . ($message->deleted_at ?? 'null'));
        
        // 論理削除を実行
        $this->info("\nPerforming soft delete...");
        $message->delete();
        
        // 削除後の状態を確認
        $this->info("After soft delete:");
        $this->line("- Deleted at: " . ($message->deleted_at ?? 'null'));
        
        // 通常のクエリでは取得されないことを確認
        $normalQuery = Message::where('id', $message->id)->first();
        $this->info("Normal query result: " . ($normalQuery ? 'Found' : 'Not found'));
        
        // withTrashedで取得できることを確認
        $withTrashed = Message::withTrashed()->where('id', $message->id)->first();
        $this->info("WithTrashed query result: " . ($withTrashed ? 'Found' : 'Not found'));
        if ($withTrashed) {
            $this->line("- Deleted at: " . ($withTrashed->deleted_at ?? 'null'));
        }
        
        // 復元
        $this->info("\nRestoring message...");
        $withTrashed->restore();
        
        // 復元後の状態を確認
        $this->info("After restore:");
        $this->line("- Deleted at: " . ($withTrashed->deleted_at ?? 'null'));
        
        $normalQueryAfterRestore = Message::where('id', $message->id)->first();
        $this->info("Normal query after restore: " . ($normalQueryAfterRestore ? 'Found' : 'Not found'));
        
        $this->info("\n✅ Soft delete test completed successfully!");
    }
} 