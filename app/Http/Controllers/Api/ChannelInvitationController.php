<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ChannelInvitationMail;
use App\Models\Channel;
use App\Models\ChannelInvitation;
use App\Models\User;
use App\Repositories\Interfaces\ChannelInvitationRepositoryInterface;
use App\Repositories\Interfaces\ChannelMemberRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ChannelInvitationController extends Controller
{
    protected $invitationRepository;
    protected $memberRepository;

    public function __construct(
        ChannelInvitationRepositoryInterface $invitationRepository,
        ChannelMemberRepositoryInterface $memberRepository
    ) {
        $this->invitationRepository = $invitationRepository;
        $this->memberRepository = $memberRepository;
    }

    /**
     * チャンネルにユーザーを招待
     */
    public function invite(Request $request, string $channelId): JsonResponse
    {
        try {
            $channel = Channel::findOrFail($channelId);
            $currentUser = auth()->user();

            // 権限チェック: チャンネルの管理者のみ招待可能
            if (!$this->memberRepository->isAdmin($channelId, $currentUser->id)) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only channel admins can invite users'
                ], 403);
            }

            $validated = $request->validate([
                'user_ids' => 'sometimes|array',
                'user_ids.*' => 'integer|exists:tbl_users,id',
                'emails' => 'sometimes|array',
                'emails.*' => 'email',
            ]);

            $invitedUsers = [];
            $errors = [];

            // ユーザーIDによる招待処理
            if (isset($validated['user_ids']) && !empty($validated['user_ids'])) {
                foreach ($validated['user_ids'] as $userId) {
                    // 既にメンバーかチェック
                    if ($this->memberRepository->isMember($channelId, $userId)) {
                        $errors[] = "User ID {$userId} is already a member of this channel";
                        continue;
                    }

                    // 既に招待中かチェック
                    $existingInvitation = $this->invitationRepository->checkExistingInvitation($channelId, $userId);
                    if ($existingInvitation) {
                        $errors[] = "User ID {$userId} already has a pending invitation";
                        continue;
                    }

                    // 招待を作成
                    $invitation = $this->invitationRepository->create([
                        'channel_id' => $channelId,
                        'inviter_id' => $currentUser->id,
                        'invitee_id' => $userId,
                        'status' => 'pending',
                        'expires_at' => Carbon::now()->addDays(7), // 7日間有効
                    ]);

                    // メール送信
                    try {
                        Mail::to($invitation->invitee->email)->send(new ChannelInvitationMail($invitation));
                        Log::info('Invitation email sent successfully', [
                            'invitation_id' => $invitation->id,
                            'invitee_email' => $invitation->invitee->email
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to send invitation email', [
                            'invitation_id' => $invitation->id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    $invitedUsers[] = $invitation->load('invitee');
                }
            }

            // メールアドレスによる招待処理
            if (isset($validated['emails']) && !empty($validated['emails'])) {
                foreach ($validated['emails'] as $email) {
                    // 既存ユーザーかチェック
                    $existingUser = User::where('email', $email)->first();
                    
                    if ($existingUser) {
                        // 既存ユーザーの場合
                        if ($this->memberRepository->isMember($channelId, $existingUser->id)) {
                            $errors[] = "User with email {$email} is already a member of this channel";
                            continue;
                        }

                        // 既に招待中かチェック
                        $existingInvitation = $this->invitationRepository->checkExistingInvitation($channelId, $existingUser->id);
                        if ($existingInvitation) {
                            $errors[] = "User with email {$email} already has a pending invitation";
                            continue;
                        }

                        // 招待を作成
                        $invitation = $this->invitationRepository->create([
                            'channel_id' => $channelId,
                            'inviter_id' => $currentUser->id,
                            'invitee_id' => $existingUser->id,
                            'status' => 'pending',
                            'expires_at' => Carbon::now()->addDays(7), // 7日間有効
                        ]);

                        // メール送信
                        try {
                            Mail::to($invitation->invitee->email)->send(new ChannelInvitationMail($invitation));
                            Log::info('Invitation email sent successfully', [
                                'invitation_id' => $invitation->id,
                                'invitee_email' => $invitation->invitee->email
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send invitation email', [
                                'invitation_id' => $invitation->id,
                                'error' => $e->getMessage()
                            ]);
                        }

                        $invitedUsers[] = $invitation->load('invitee');
                    } else {
                        // 新規ユーザーの場合（メールアドレスのみ保存）
                        // 既に同じメールアドレスで招待中かチェック
                        $existingInvitation = $this->invitationRepository->checkExistingInvitation($channelId, null, $email);
                        if ($existingInvitation) {
                            $errors[] = "Email {$email} already has a pending invitation";
                            continue;
                        }

                        // 招待を作成（invitee_idはnull、invitee_emailを保存）
                        $invitation = $this->invitationRepository->create([
                            'channel_id' => $channelId,
                            'inviter_id' => $currentUser->id,
                            'invitee_id' => null,
                            'invitee_email' => $email,
                            'status' => 'pending',
                            'expires_at' => Carbon::now()->addDays(7), // 7日間有効
                        ]);

                        // メール送信
                        try {
                            Mail::to($email)->send(new ChannelInvitationMail($invitation));
                            Log::info('Invitation email sent successfully', [
                                'invitation_id' => $invitation->id,
                                'invitee_email' => $email
                            ]);
                        } catch (\Exception $e) {
                            Log::error('Failed to send invitation email', [
                                'invitation_id' => $invitation->id,
                                'error' => $e->getMessage()
                            ]);
                        }

                        $invitedUsers[] = $invitation;
                    }
                }
            }

            Log::info('Channel invitations sent', [
                'channel_id' => $channelId,
                'inviter_id' => $currentUser->id,
                'invited_users' => $invitedUsers,
                'errors' => $errors
            ]);

            return response()->json([
                'success' => true,
                'invited_users' => $invitedUsers,
                'errors' => $errors
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for channel invitation', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Channel invitation failed', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Channel invitation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * チャンネルの招待一覧を取得
     */
    public function index(string $channelId): JsonResponse
    {
        try {
            $channel = Channel::findOrFail($channelId);
            $currentUser = auth()->user();

            // 権限チェック: チャンネルの管理者のみ閲覧可能
            if (!$this->memberRepository->isAdmin($channelId, $currentUser->id)) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'Only channel admins can view invitations'
                ], 403);
            }

            $invitations = $this->invitationRepository->getByChannelId($channelId)
                ->map(function ($invitation) {
                    return [
                        'id' => $invitation->id,
                        'status' => $invitation->status,
                        'expires_at' => $invitation->expires_at,
                        'created_at' => $invitation->created_at,
                        'invitee' => [
                            'id' => $invitation->invitee->id,
                            'name' => $invitation->invitee->name,
                            'email' => $invitation->invitee->email,
                        ],
                        'inviter' => [
                            'id' => $invitation->inviter->id,
                            'name' => $invitation->inviter->name,
                        ],
                    ];
                });

            return response()->json($invitations);

        } catch (\Exception $e) {
            Log::error('Failed to get channel invitations', [
                'error' => $e->getMessage(),
                'channel_id' => $channelId
            ]);
            
            return response()->json([
                'error' => 'Failed to get channel invitations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 招待を承認/拒否
     */
    public function respond(Request $request, string $invitationId): JsonResponse
    {
        try {
            $invitation = $this->invitationRepository->findByIdWithRelations($invitationId, ['channel', 'invitee']);
            if (!$invitation) {
                return response()->json([
                    'error' => 'Invitation not found',
                    'message' => 'The specified invitation does not exist'
                ], 404);
            }

            $currentUser = auth()->user();

            // 権限チェック: 招待されたユーザーのみ応答可能
            if ($invitation->invitee_id !== $currentUser->id && 
                $invitation->invitee_email !== $currentUser->email) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only respond to invitations sent to you'
                ], 403);
            }

            // 招待が有効かチェック
            if (!$invitation->isValid()) {
                return response()->json([
                    'error' => 'Invalid invitation',
                    'message' => 'This invitation has expired or is no longer valid'
                ], 400);
            }

            $validated = $request->validate([
                'action' => 'required|in:accept,decline',
            ]);

            if ($validated['action'] === 'accept') {
                // チャンネルメンバーに追加
                $this->memberRepository->create([
                    'channel_id' => $invitation->channel_id,
                    'user_id' => $currentUser->id,
                    'role' => 'member',
                ]);

                // メールアドレスによる招待の場合は、invitee_idを更新
                if ($invitation->invitee_email && !$invitation->invitee_id) {
                    $this->invitationRepository->update($invitation, ['invitee_id' => $currentUser->id]);
                }

                $this->invitationRepository->accept($invitation);

                Log::info('Channel invitation accepted', [
                    'invitation_id' => $invitationId,
                    'user_id' => $currentUser->id,
                    'channel_id' => $invitation->channel_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Successfully joined the channel'
                ]);

            } else {
                $this->invitationRepository->decline($invitation);

                Log::info('Channel invitation declined', [
                    'invitation_id' => $invitationId,
                    'user_id' => $currentUser->id,
                    'channel_id' => $invitation->channel_id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Invitation declined'
                ]);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for invitation response', ['errors' => $e->errors()]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Invitation response failed', [
                'error' => $e->getMessage(),
                'invitation_id' => $invitationId,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Invitation response failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 招待を取り消し
     */
    public function cancel(string $invitationId): JsonResponse
    {
        try {
            $invitation = $this->invitationRepository->findByIdWithRelations($invitationId, ['channel']);
            if (!$invitation) {
                return response()->json([
                    'error' => 'Invitation not found',
                    'message' => 'The specified invitation does not exist'
                ], 404);
            }

            $currentUser = auth()->user();

            // 権限チェック: 招待者またはチャンネル管理者のみ取り消し可能
            if ($invitation->inviter_id !== $currentUser->id && 
                !$this->memberRepository->isAdmin($invitation->channel_id, $currentUser->id)) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only cancel invitations you sent or manage as admin'
                ], 403);
            }

            $this->invitationRepository->delete($invitation);

            Log::info('Channel invitation cancelled', [
                'invitation_id' => $invitationId,
                'cancelled_by' => $currentUser->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Invitation cancelled successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cancel invitation', [
                'error' => $e->getMessage(),
                'invitation_id' => $invitationId
            ]);
            
            return response()->json([
                'error' => 'Failed to cancel invitation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ユーザーが受け取った招待一覧を取得
     */
    public function myInvitations(): JsonResponse
    {
        try {
            $currentUser = auth()->user();

            $invitations = $this->invitationRepository->getByUserId($currentUser->id)
                ->merge($this->invitationRepository->getByEmail($currentUser->email))
                ->unique('id')
                ->map(function ($invitation) {
                    return [
                        'id' => $invitation->id,
                        'expires_at' => $invitation->expires_at,
                        'created_at' => $invitation->created_at,
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

            return response()->json($invitations);

        } catch (\Exception $e) {
            Log::error('Failed to get user invitations', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
            
            return response()->json([
                'error' => 'Failed to get invitations',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 招待情報を取得（未認証ユーザー向け）
     */
    public function getInvitationInfo(string $invitationId): JsonResponse
    {
        try {
            $invitation = $this->invitationRepository->findValidById($invitationId);

            if (!$invitation) {
                return response()->json([
                    'error' => 'Invitation not found or expired',
                    'message' => 'This invitation does not exist or has expired'
                ], 404);
            }

            return response()->json([
                'id' => $invitation->id,
                'expires_at' => $invitation->expires_at,
                'created_at' => $invitation->created_at,
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

        } catch (\Exception $e) {
            Log::error('Failed to get invitation info', [
                'error' => $e->getMessage(),
                'invitation_id' => $invitationId
            ]);
            
            return response()->json([
                'error' => 'Failed to get invitation info',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
