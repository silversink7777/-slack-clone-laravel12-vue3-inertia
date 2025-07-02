<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckCurrentUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check current user information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking user information...');
        
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->error('No users found in database');
            return;
        }
        
        $this->info("Total users: " . $users->count());
        
        foreach ($users as $user) {
            $this->line("- ID: {$user->id}, Name: {$user->name}, Email: {$user->email}");
        }
        
        // 最新のユーザーを推奨
        $latestUser = User::latest()->first();
        $this->info("\nRecommended user for testing:");
        $this->line("- ID: {$latestUser->id}");
        $this->line("- Name: {$latestUser->name}");
        $this->line("- Email: {$latestUser->email}");
    }
} 