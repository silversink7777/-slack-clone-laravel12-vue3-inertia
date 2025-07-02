<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Repositories\Interfaces\ChannelMemberRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected $messageRepository;
    protected $memberRepository;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        ChannelMemberRepositoryInterface $memberRepository
    ) {
        $this->messageRepository = $messageRepository;
        $this->memberRepository = $memberRepository;
    }

    /**
     * Display a listing of the resource.
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

            $messages = $this->messageRepository->getByChannelId($channelId)
                ->map(fn ($message) => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'channel_id' => $message->channel_id,
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'avatar' => $message->user->profile_photo_url,
                    ],
                    'time' => $message->created_at->format('g:i A'),
                    'created_at' => $message->created_at,
                ]);

            return response()->json($messages);
        } catch (\Exception $e) {
            Log::error('Message fetching failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Message fetching failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Message creation request received', ['data' => $request->all()]);
            
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
                'channel_id' => 'required|exists:tbl_channels,id',
            ]);

            // ユーザーがチャンネルのメンバーかどうかをチェック
            if (!$this->memberRepository->isMember($validated['channel_id'], auth()->id())) {
                return response()->json(['error' => 'Unauthorized access to channel'], 403);
            }

            Log::info('Validation passed', ['validated' => $validated]);

            $message = $this->messageRepository->create([
                'content' => $validated['content'],
                'channel_id' => $validated['channel_id'],
                'user_id' => auth()->id(),
            ]);

            // ユーザー情報を含めて返却
            $message->load('user');

            Log::info('Message created successfully', ['message_id' => $message->id, 'message' => $message->toArray()]);

            return response()->json([
                'id' => $message->id,
                'content' => $message->content,
                'channel_id' => $message->channel_id,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->profile_photo_url,
                ],
                'time' => $message->created_at->format('g:i A'),
                'created_at' => $message->created_at,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Message creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Message creation failed',
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
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            Log::info('Message update request received', ['message_id' => $id, 'data' => $request->all()]);
            
            $message = $this->messageRepository->findById($id);
            
            if (!$message) {
                return response()->json(['error' => 'Message not found'], 404);
            }
            
            // 自分のメッセージのみ編集可能
            if (!$this->messageRepository->isUserMessage($id, auth()->id())) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only edit your own messages'
                ], 403);
            }
            
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            Log::info('Validation passed', ['validated' => $validated]);

            $this->messageRepository->update($message, [
                'content' => $validated['content'],
            ]);

            // ユーザー情報を含めて返却
            $message->load('user');

            Log::info('Message updated successfully', ['message_id' => $message->id, 'message' => $message->toArray()]);

            return response()->json([
                'id' => $message->id,
                'content' => $message->content,
                'channel_id' => $message->channel_id,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->profile_photo_url,
                ],
                'time' => $message->created_at->format('g:i A'),
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Message update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Message update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $message = $this->messageRepository->findById($id);
            
            if (!$message) {
                return response()->json(['error' => 'Message not found'], 404);
            }
            
            // 自分のメッセージのみ削除可能
            if (!$this->messageRepository->isUserMessage($id, auth()->id())) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only delete your own messages'
                ], 403);
            }
            
            $this->messageRepository->delete($message);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Message delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore the specified soft deleted resource.
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $message = Message::withTrashed()->findOrFail($id);
            
            // 自分のメッセージのみ復元可能
            if (!$this->messageRepository->isUserMessage($id, auth()->id())) {
                return response()->json([
                    'error' => 'Unauthorized',
                    'message' => 'You can only restore your own messages'
                ], 403);
            }
            
            $this->messageRepository->restore($message);
            
            // ユーザー情報を含めて返却
            $message->load('user');
            
            return response()->json([
                'id' => $message->id,
                'content' => $message->content,
                'channel_id' => $message->channel_id,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'avatar' => $message->user->profile_photo_url,
                ],
                'time' => $message->created_at->format('g:i A'),
                'created_at' => $message->created_at,
                'updated_at' => $message->updated_at,
            ]);
        } catch (\Exception $e) {
            Log::error('Message restore failed', [
                'error' => $e->getMessage(),
                'message_id' => $id
            ]);
            
            return response()->json([
                'error' => 'Message restore failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * メッセージを検索
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query('q', '');
            $userId = auth()->id();
            
            if (empty($query)) {
                return response()->json(['messages' => []]);
            }
            
            // ユーザーが参加しているチャンネルIDを取得
            $channelIds = \App\Models\ChannelMember::where('user_id', $userId)
                ->pluck('channel_id')
                ->toArray();
            
            if (empty($channelIds)) {
                return response()->json(['messages' => []]);
            }
            
            $messages = $this->messageRepository->searchByContent($query, $channelIds, 20);
            
            $formattedMessages = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'channel' => [
                        'id' => $message->channel->id,
                        'name' => $message->channel->name,
                    ],
                    'user' => [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'avatar' => $message->user->profile_photo_url,
                    ],
                    'time' => $message->created_at->format('g:i A'),
                    'date' => $message->created_at->format('M j, Y'),
                    'created_at' => $message->created_at,
                ];
            });
            
            return response()->json(['messages' => $formattedMessages]);
        } catch (\Exception $e) {
            Log::error('Message search failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Message search failed'], 500);
        }
    }
}
