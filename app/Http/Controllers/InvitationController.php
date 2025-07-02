<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\User;
use App\Models\ChannelInvitation;
use App\Models\UserOnlineStatus;
use App\Repositories\Interfaces\ChannelInvitationRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class InvitationController extends Controller
{
    protected $invitationRepository;

    public function __construct(ChannelInvitationRepositoryInterface $invitationRepository)
    {
        $this->invitationRepository = $invitationRepository;
    }

    public function index(Request $request): Response
    {
        // 認証状態をチェック
        $isAuthenticated = auth()->check();
        
        if ($isAuthenticated) {
            // 認証済みユーザーの場合
            $invitations = $this->getAuthenticatedUserInvitations();
        } else {
            // 未認証ユーザーの場合
            $invitations = $this->getUnauthenticatedUserInvitations($request);
        }

        return Inertia::render('Invitations', [
            'invitations' => $invitations,
            'isAuthenticated' => $isAuthenticated,
            'channels' => $isAuthenticated ? auth()->user()->channels : [],
            'direct-messages' => $isAuthenticated ? [] : [], // 未認証ユーザーにはDMは表示しない
            'messages' => [],
            'manualOffline' => false,
        ]);
    }

    /**
     * 認証済みユーザーの招待一覧を取得
     */
    private function getAuthenticatedUserInvitations()
    {
        // ユーザーが受け取った招待一覧を取得
        $invitations = $this->invitationRepository->getByUserId(auth()->id())
            ->merge($this->invitationRepository->getByEmail(auth()->user()->email))
            ->unique('id')
            ->map(function ($invitation) {
                return [
                    'id' => $invitation->id,
                    'expires_at' => $invitation->expires_at,
                    'created_at' => $invitation->created_at,
                    'invitee_id' => $invitation->invitee_id,
                    'invitee_email' => $invitation->invitee_email,
                    'channel' => [
                        'id' => $invitation->channel->id,
                        'name' => $invitation->channel->name,
                    ],
                    'inviter' => [
                        'id' => $invitation->inviter->id,
                        'name' => $invitation->inviter->name,
                    ],
                ];
            });

        // デバッグ情報をログに出力
        Log::info('InvitationController debug info', [
            'user_id' => auth()->id(),
            'invitations_count' => $invitations->count(),
            'invitations_data' => $invitations->toArray(),
            'raw_invitations_count' => ChannelInvitation::where('invitee_id', auth()->id())->count(),
            'pending_invitations_count' => ChannelInvitation::where('invitee_id', auth()->id())->where('status', 'pending')->count(),
            'valid_invitations_count' => ChannelInvitation::where('invitee_id', auth()->id())->where('status', 'pending')->where('expires_at', '>', now())->count(),
        ]);

        return $invitations;
    }

    /**
     * 未認証ユーザーの招待一覧を取得
     */
    private function getUnauthenticatedUserInvitations(Request $request)
    {
        $invitations = collect();

        // URLパラメータから招待IDを取得
        $invitationId = $request->get('invitation');
        
        if ($invitationId) {
            $invitation = $this->invitationRepository->findValidById($invitationId);

            if ($invitation) {
                $invitations->push([
                    'id' => $invitation->id,
                    'expires_at' => $invitation->expires_at,
                    'created_at' => $invitation->created_at,
                    'invitee_id' => $invitation->invitee_id,
                    'invitee_email' => $invitation->invitee_email,
                    'channel' => [
                        'id' => $invitation->channel->id,
                        'name' => $invitation->channel->name,
                    ],
                    'inviter' => [
                        'id' => $invitation->inviter->id,
                        'name' => $invitation->inviter->name,
                    ],
                ]);
            }
        }

        return $invitations;
    }
} 