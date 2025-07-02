<?php

namespace App\Console\Commands;

use App\Models\ChannelInvitation;
use App\Repositories\Interfaces\ChannelInvitationRepositoryInterface;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ExpireChannelInvitations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire channel invitations that have passed their expiration date';

    protected $invitationRepository;

    public function __construct(ChannelInvitationRepositoryInterface $invitationRepository)
    {
        parent::__construct();
        $this->invitationRepository = $invitationRepository;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to expire channel invitations...');

        $expiredInvitations = ChannelInvitation::where('status', 'pending')
            ->where('expires_at', '<', Carbon::now())
            ->get();

        $count = 0;
        foreach ($expiredInvitations as $invitation) {
            $this->invitationRepository->markAsExpired($invitation);
            $count++;
        }

        $this->info("Expired {$count} channel invitations successfully.");

        return Command::SUCCESS;
    }
}
