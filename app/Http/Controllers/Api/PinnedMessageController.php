<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\PinnedMessageRepositoryInterface;
use App\Repositories\Interfaces\ChannelMemberRepositoryInterface;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PinnedMessageController extends Controller
{
    protected $pinnedMessageRepository;
    protected $memberRepository;
    protected $messageRepository;

    public function __construct(
        PinnedMessageRepositoryInterface $pinnedMessageRepository,
        ChannelMemberRepositoryInterface $memberRepository,
        MessageRepositoryInterface $messageRepository
    ) {
        $this->pinnedMessageRepository = $pinnedMessageRepository;
        $this->memberRepository = $memberRepository;
        $this->messageRepository = $messageRepository;
    }

    /**
     * チャンネルのピン留めメッセージ一覧を取得
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $channelId = $request->query('channel_id');
            
            if (!$channelId) {
                return response()->json(['error' => 'Channel ID is required'], 400);
            }

            // ユーザーがチャンネルのメンバーかどうかをチェック
            if (!$this->memberRepository->isMember($channelId, auth()->id())) {
                return response()->json(['error' => 'Unauthorized access to channel'], 403);
            }

            $pinnedMessages = $this->pinnedMessageRepository->getByChannelId($channelId)
                ->map(fn ($pinnedMessage) => [
                    'id' => $pinnedMessage->id,
                    'message' => [
                        'id' => $pinnedMessage->message->id,
                        'content' => $pinnedMessage->message->content,
                        'user' => [
                            'id' => $pinnedMessage->message->user->id,
                            'name' => $pinnedMessage->message->user->name,
                            'avatar' => $pinnedMessage->message->user->profile_photo_url,
                        ],
                        'file_path' => $pinnedMessage->message->file_path,
                        'file_name' => $pinnedMessage->message->file_name,
                        'file_mime' => $pinnedMessage->message->file_mime,
                        'file_size' => $pinnedMessage->message->file_size,
                        'time' => $pinnedMessage->message->created_at->format('g:i A'),
                        'created_at' => $pinnedMessage->message->created_at,
                    ],
                    'pinned_by' => [
                        'id' => $pinnedMessage->pinnedByUser->id,
                        'name' => $pinnedMessage->pinnedByUser->name,
                    ],
                    'pinned_at' => $pinnedMessage->created_at,
                ]);

            return response()->json($pinnedMessages);
        } catch (\Exception $e) {
            Log::error('Pinned messages fetching failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Pinned messages fetching failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * メッセージをピン留め
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message_id' => 'required|exists:tbl_messages,id',
                'channel_id' => 'required|exists:tbl_channels,id',
            ]);

            // ユーザーがチャンネルのメンバーかどうかをチェック
            if (!$this->memberRepository->isMember($validated['channel_id'], auth()->id())) {
                return response()->json(['error' => 'Unauthorized access to channel'], 403);
            }

            // メッセージが既にピン留めされているかチェック
            if ($this->pinnedMessageRepository->isPinned($validated['message_id'], $validated['channel_id'])) {
                return response()->json(['error' => 'Message is already pinned'], 422);
            }

            $pinnedMessage = $this->pinnedMessageRepository->create([
                'message_id' => $validated['message_id'],
                'channel_id' => $validated['channel_id'],
                'pinned_by' => auth()->id(),
            ]);

            $pinnedMessage->load(['message.user', 'pinnedByUser']);

            return response()->json([
                'id' => $pinnedMessage->id,
                'message' => [
                    'id' => $pinnedMessage->message->id,
                    'content' => $pinnedMessage->message->content,
                    'user' => [
                        'id' => $pinnedMessage->message->user->id,
                        'name' => $pinnedMessage->message->user->name,
                        'avatar' => $pinnedMessage->message->user->profile_photo_url,
                    ],
                    'file_path' => $pinnedMessage->message->file_path,
                    'file_name' => $pinnedMessage->message->file_name,
                    'file_mime' => $pinnedMessage->message->file_mime,
                    'file_size' => $pinnedMessage->message->file_size,
                    'time' => $pinnedMessage->message->created_at->format('g:i A'),
                    'created_at' => $pinnedMessage->message->created_at,
                ],
                'pinned_by' => [
                    'id' => $pinnedMessage->pinnedByUser->id,
                    'name' => $pinnedMessage->pinnedByUser->name,
                ],
                'pinned_at' => $pinnedMessage->created_at,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Message pinning failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Message pinning failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ピン留めを削除
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $pinnedMessage = $this->pinnedMessageRepository->findById($id);
            
            if (!$pinnedMessage) {
                return response()->json(['error' => 'Pinned message not found'], 404);
            }

            // ユーザーがチャンネルのメンバーかどうかをチェック
            if (!$this->memberRepository->isMember($pinnedMessage->channel_id, auth()->id())) {
                return response()->json(['error' => 'Unauthorized access to channel'], 403);
            }

            $this->pinnedMessageRepository->delete($id);

            return response()->json(['message' => 'Pinned message removed successfully']);
        } catch (\Exception $e) {
            Log::error('Pinned message removal failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Pinned message removal failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * メッセージIDとチャンネルIDを使用してピン留めを削除
     */
    public function destroyByMessageAndChannel(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message_id' => 'required|exists:tbl_messages,id',
                'channel_id' => 'required|exists:tbl_channels,id',
            ]);

            // ユーザーがチャンネルのメンバーかどうかをチェック
            if (!$this->memberRepository->isMember($validated['channel_id'], auth()->id())) {
                return response()->json(['error' => 'Unauthorized access to channel'], 403);
            }

            $pinnedMessage = $this->pinnedMessageRepository->findByMessageAndChannel(
                $validated['message_id'], 
                $validated['channel_id']
            );
            
            if (!$pinnedMessage) {
                return response()->json(['error' => 'Pinned message not found'], 404);
            }

            $this->pinnedMessageRepository->delete($pinnedMessage->id);

            return response()->json(['message' => 'Pinned message removed successfully']);
        } catch (\Exception $e) {
            Log::error('Pinned message removal failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Pinned message removal failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * メッセージがピン留めされているかチェック
     */
    public function check(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message_id' => 'required|exists:tbl_messages,id',
                'channel_id' => 'required|exists:tbl_channels,id',
            ]);

            $isPinned = $this->pinnedMessageRepository->isPinned($validated['message_id'], $validated['channel_id']);

            return response()->json(['is_pinned' => $isPinned]);
        } catch (\Exception $e) {
            Log::error('Pinned message check failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Pinned message check failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
