<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Models\User;
use Illuminate\Console\Command;

class TestMessageEdit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:message-edit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test message edit functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing message edit functionality...');
        
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
        $this->line("- Created: {$message->created_at}");
        $this->line("- Updated: {$message->updated_at}");
        
        // メッセージを編集
        $originalContent = $message->content;
        $newContent = "Edited: " . $originalContent;
        
        $message->update(['content' => $newContent]);
        $message->refresh();
        
        $this->info("\nMessage updated successfully:");
        $this->line("- New content: {$message->content}");
        $this->line("- Updated at: {$message->updated_at}");
        
        // 元に戻す
        $message->update(['content' => $originalContent]);
        $this->info("\nMessage restored to original content");
        
        // ルートの確認
        $this->info("\nChecking routes...");
        $routes = \Route::getRoutes();
        $updateRoute = null;
        
        foreach ($routes as $route) {
            if ($route->getName() === 'messages.update') {
                $updateRoute = $route;
                break;
            }
        }
        
        if ($updateRoute) {
            $this->info('✅ Message update route exists');
            $this->line("Route: " . $updateRoute->uri());
            $this->line("Method: " . implode('|', $updateRoute->methods()));
        } else {
            $this->error('❌ Message update route not found');
        }
    }
} 