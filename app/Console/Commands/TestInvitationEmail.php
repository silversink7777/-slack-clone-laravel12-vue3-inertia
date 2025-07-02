<?php

namespace App\Console\Commands;

use App\Mail\ChannelInvitationMail;
use App\Models\ChannelInvitation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestInvitationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:invitation-email {email} {--invitation-id=} {--create-unregistered}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test invitation email sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $invitationId = $this->option('invitation-id');
        $createUnregistered = $this->option('create-unregistered');

        if ($invitationId) {
            // 既存の招待を使用
            $invitation = ChannelInvitation::with(['channel', 'inviter'])->find($invitationId);
            if (!$invitation) {
                $this->error("Invitation with ID {$invitationId} not found.");
                return 1;
            }
        } elseif ($createUnregistered) {
            // 未登録ユーザー向けの招待を作成
            $this->info("Creating invitation for unregistered user...");
            
            // 最初のチャンネルとユーザーを取得
            $channel = \App\Models\Channel::first();
            $inviter = \App\Models\User::first();
            
            if (!$channel || !$inviter) {
                $this->error("No channels or users found. Please create some first.");
                return 1;
            }
            
            $invitation = ChannelInvitation::create([
                'channel_id' => $channel->id,
                'inviter_id' => $inviter->id,
                'invitee_id' => null,
                'invitee_email' => $email,
                'status' => 'pending',
                'expires_at' => \Carbon\Carbon::now()->addDays(7),
            ]);
            
            $invitation->load(['channel', 'inviter']);
            $this->info("Created invitation ID: {$invitation->id}");
            
        } else {
            // テスト用の招待を使用
            $invitation = ChannelInvitation::with(['channel', 'inviter'])->first();
            if (!$invitation) {
                $this->error("No invitations found in database. Please create an invitation first.");
                return 1;
            }
        }

        $this->info("Sending test invitation email to: {$email}");
        $this->info("Using invitation ID: {$invitation->id}");
        $this->info("Channel: {$invitation->channel->name}");
        $this->info("Inviter: {$invitation->inviter->name}");
        
        if ($invitation->invitee_id) {
            $this->info("Type: Existing user invitation");
        } else {
            $this->info("Type: Unregistered user invitation");
            $this->info("Registration URL: " . url("/register?invitation={$invitation->id}&email=" . urlencode($email)));
        }

        try {
            Mail::to($email)->send(new ChannelInvitationMail($invitation));
            $this->info("✅ Invitation email sent successfully!");
        } catch (\Exception $e) {
            $this->error("❌ Failed to send invitation email: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
