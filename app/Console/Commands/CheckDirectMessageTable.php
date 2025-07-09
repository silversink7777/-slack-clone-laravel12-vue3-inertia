<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class CheckDirectMessageTable extends Command
{
    protected $signature = 'test:check-dm-table';
    protected $description = 'Check DirectMessage table structure';

    public function handle()
    {
        $this->info('Checking tbl_direct_messages table...');
        
        if (!Schema::hasTable('tbl_direct_messages')) {
            $this->error('Table tbl_direct_messages does not exist!');
            return 1;
        }
        
        $this->info('Table exists. Checking columns...');
        
        $columns = Schema::getColumnListing('tbl_direct_messages');
        foreach ($columns as $column) {
            $this->line("  - {$column}");
        }
        
        $this->info('Table structure check completed.');
        return 0;
    }
} 