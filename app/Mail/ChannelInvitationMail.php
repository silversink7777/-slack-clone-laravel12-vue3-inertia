<?php

namespace App\Mail;

use App\Models\ChannelInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChannelInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitation;
    public $channel;
    public $inviter;
    public $inviteeEmail;

    /**
     * Create a new message instance.
     */
    public function __construct(ChannelInvitation $invitation)
    {
        $this->invitation = $invitation;
        $this->channel = $invitation->channel;
        $this->inviter = $invitation->inviter;
        $this->inviteeEmail = $invitation->invitee_email ?? $invitation->invitee->email ?? 'Unknown';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "【{$this->channel->name}】チャンネルへの招待",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.channel-invitation',
            with: [
                'invitation' => $this->invitation,
                'channel' => $this->channel,
                'inviter' => $this->inviter,
                'inviteeEmail' => $this->inviteeEmail,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
