<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\ChannelInvitation;
use App\Models\User;
use App\Repositories\Interfaces\ChannelInvitationRepositoryInterface;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CreateTestInvitation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-invitation {channel_id} {inviter_id} {--invitee-id=} {--email=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test invitation';

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
        $channelId = $this->argument('channel_id');
        $inviterId = $this->argument('inviter_id');
        $inviteeId = $this->option('invitee-id');
        $email = $this->option('email');

        $channel = Channel::find($channelId);
        if (!$channel) {
            $this->error("Channel with ID {$channelId} not found");
            return 1;
        }

        $inviter = User::find($inviterId);
        if (!$inviter) {
            $this->error("Inviter with ID {$inviterId} not found");
            return 1;
        }

        // 既存ユーザーへの招待か、メールアドレスへの招待かを判定
        if ($inviteeId) {
            $invitee = User::find($inviteeId);
            if (!$invitee) {
                $this->error("Invitee with ID {$inviteeId} not found");
                return 1;
            }

            // 既に招待中かチェック
            $existingInvitation = $this->invitationRepository->checkExistingInvitation($channelId, $inviteeId);

            if ($existingInvitation) {
                $this->warn("Invitation already exists for user {$invitee->name} to channel '{$channel->name}'");
                return 0;
            }

            // 招待を作成
            $invitation = $this->invitationRepository->create([
                'channel_id' => $channelId,
                'inviter_id' => $inviterId,
                'invitee_id' => $inviteeId,
                'status' => 'pending',
                'expires_at' => Carbon::now()->addDays(7),
            ]);

            $this->info("✅ Invitation created successfully!");
            $this->info("Channel: {$channel->name}");
            $this->info("Inviter: {$inviter->name}");
            $this->info("Invitee: {$invitee->name} ({$invitee->email})");
            $this->info("Invitation ID: {$invitation->id}");

        } elseif ($email) {
            // メールアドレスへの招待（未登録ユーザー向け）
            // 既に同じメールアドレスで招待中かチェック
            $existingInvitation = $this->invitationRepository->checkExistingInvitation($channelId, null, $email);

            if ($existingInvitation) {
                $this->warn("Invitation already exists for email {$email} to channel '{$channel->name}'");
                return 0;
            }

            // 招待を作成
            $invitation = $this->invitationRepository->create([
                'channel_id' => $channelId,
                'inviter_id' => $inviterId,
                'invitee_id' => null,
                'invitee_email' => $email,
                'status' => 'pending',
                'expires_at' => Carbon::now()->addDays(7),
            ]);

            $this->info("✅ Invitation created successfully for unregistered user!");
            $this->info("Channel: {$channel->name}");
            $this->info("Inviter: {$inviter->name}");
            $this->info("Invitee Email: {$email}");
            $this->info("Invitation ID: {$invitation->id}");
            $this->info("Registration URL: " . url("/register?invitation={$invitation->id}&email=" . urlencode($email)));

        } else {
            $this->error("Please specify either --invitee-id or --email option");
            return 1;
        }

        return 0;
    }
}
