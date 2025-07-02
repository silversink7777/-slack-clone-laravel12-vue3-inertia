<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelMember;
use Illuminate\Console\Command;

class CheckChannelMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:channel-members';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check channel members and their roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking channel members...');

        $channels = Channel::with('members.user')->get();

        foreach ($channels as $channel) {
            $this->info("\nChannel: {$channel->name} (ID: {$channel->id})");
            
            if ($channel->members->isEmpty()) {
                $this->warn('  No members found');
                continue;
            }

            foreach ($channel->members as $member) {
                $role = $member->role ?? 'member';
                $this->line("  - {$member->user->name} (ID: {$member->user->id}) - Role: {$role}");
            }
        }

        $this->info("\nCheck completed!");
    }
} 