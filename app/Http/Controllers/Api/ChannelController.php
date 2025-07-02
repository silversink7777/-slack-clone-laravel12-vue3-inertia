<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\ChannelMember;
use App\Repositories\Interfaces\ChannelRepositoryInterface;
use App\Repositories\Interfaces\ChannelMemberRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChannelController extends Controller
{
    protected $channelRepository;
    protected $memberRepository;

    public function __construct(
        ChannelRepositoryInterface $channelRepository,
        ChannelMemberRepositoryInterface $memberRepository
    ) {
        $this->channelRepository = $channelRepository;
        $this->memberRepository = $memberRepository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // 認証されたユーザーがメンバーであるチャンネルのみを取得
            $channels = $this->channelRepository->getByUserId(auth()->id());

            return response()->json([
                'channels' => $channels->map(fn ($channel) => [
                    'id' => $channel->id,
                    'name' => $channel->name,
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get channels', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get channels'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Channel creation request received', ['data' => $request->all()]);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:tbl_channels,name',
            ]);

            Log::info('Validation passed', ['validated' => $validated]);

            $channel = $this->channelRepository->create($validated);

            // チャンネル作成者を管理者として追加
            $this->memberRepository->create([
                'channel_id' => $channel->id,
                'user_id' => auth()->id(),
                'role' => 'admin',
            ]);

            Log::info('Channel created successfully', ['channel_id' => $channel->id, 'channel' => $channel->toArray()]);

            return response()->json($channel, 201);
        } catch (\Exception $e) {
            Log::error('Channel creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Channel creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $channel = $this->channelRepository->findById($id);
            
            if (!$channel) {
                return response()->json(['error' => 'Channel not found'], 404);
            }
            
            // ユーザーがチャンネルの管理者であることを確認
            if (!$this->channelRepository->isUserAdmin($id, auth()->id())) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $this->channelRepository->delete($channel);

            return response()->json(['message' => 'Channel deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Channel deletion failed', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['error' => 'Channel deletion failed'], 500);
        }
    }

    /**
     * チャンネルのメンバー一覧を取得
     */
    public function members(string $id): JsonResponse
    {
        try {
            $channel = $this->channelRepository->findByIdWithRelations($id, ['members.user']);
            
            if (!$channel) {
                return response()->json(['error' => 'Channel not found'], 404);
            }
            
            // ユーザーがチャンネルのメンバーであることを確認
            if (!$this->channelRepository->isUserMember($id, auth()->id())) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
            
            $members = $channel->members->map(function ($member) {
                return [
                    'id' => $member->user->id,
                    'name' => $member->user->name,
                    'email' => $member->user->email,
                    'role' => $member->role,
                    'joined_at' => $member->joined_at,
                ];
            });

            return response()->json($members);
        } catch (\Exception $e) {
            Log::error('Failed to get channel members', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['error' => 'Failed to get channel members'], 500);
        }
    }

    /**
     * チャンネルから退出
     */
    public function leave(string $id): JsonResponse
    {
        try {
            $channel = $this->channelRepository->findById($id);
            
            if (!$channel) {
                return response()->json(['error' => 'Channel not found'], 404);
            }
            
            $currentUser = auth()->user();
            
            // ユーザーがチャンネルのメンバーであることを確認
            if (!$this->memberRepository->isMember($id, $currentUser->id)) {
                return response()->json(['error' => 'You are not a member of this channel'], 403);
            }
            
            // 管理者の場合は、他の管理者がいるかチェック
            if ($this->memberRepository->isAdmin($id, $currentUser->id)) {
                $adminCount = $this->memberRepository->getAdminCount($id);
                if ($adminCount <= 1) {
                    return response()->json([
                        'error' => 'Cannot leave channel',
                        'message' => 'You are the only admin. Please transfer admin role or delete the channel.'
                    ], 400);
                }
            }
            
            // チャンネルメンバーから削除
            $this->memberRepository->removeMember($id, $currentUser->id);

            Log::info('User left channel', [
                'user_id' => $currentUser->id,
                'channel_id' => $id
            ]);

            return response()->json(['message' => 'Successfully left the channel']);
        } catch (\Exception $e) {
            Log::error('Failed to leave channel', [
                'error' => $e->getMessage(),
                'channel_id' => $id,
                'user_id' => auth()->id()
            ]);
            return response()->json(['error' => 'Failed to leave channel'], 500);
        }
    }
}
