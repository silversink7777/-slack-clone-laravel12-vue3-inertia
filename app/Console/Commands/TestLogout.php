<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class TestLogout extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:logout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test logout functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing logout functionality...');
        
        // ログアウトルートが存在するかを確認
        $routes = Route::getRoutes();
        $logoutRoute = null;
        
        foreach ($routes as $route) {
            if ($route->getName() === 'logout') {
                $logoutRoute = $route;
                break;
            }
        }
        
        if ($logoutRoute) {
            $this->info('✅ Logout route exists');
            $this->info("Route: " . $logoutRoute->uri());
            $this->info("Method: " . implode('|', $logoutRoute->methods()));
            $this->info("Name: " . $logoutRoute->getName());
        } else {
            $this->error('❌ Logout route not found');
        }
        
        // 認証関連のルートを確認
        $this->info("\nAuthentication related routes:");
        foreach ($routes as $route) {
            $name = $route->getName();
            if ($name && (str_contains($name, 'login') || str_contains($name, 'logout') || str_contains($name, 'register'))) {
                $this->line("- " . $route->uri() . " (" . $name . ") - " . implode('|', $route->methods()));
            }
        }
    }
} 