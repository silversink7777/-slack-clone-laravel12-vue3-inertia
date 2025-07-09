<?php

namespace App\Http\Controllers;

use App\Models\DirectMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DirectMessageController extends Controller
{
    /**
     * DMを開始（初回メッセージ送信）
     */
    public function startConversation(Request $request): JsonResponse
    {
        try {
            // 認証状態をログに記録
            Log::info('DM start request received', [
                'authenticated' => auth()->check(),
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            $request->validate([
                'partner_id' => 'required|integer|exists:tbl_users,id',
                'message' => 'required|string|max:1000',
            ]);

            $senderId = auth()->id();
            $partnerId = $request->partner_id;
            $message = $request->message;

            Log::info('Starting DM conversation', [
                'sender_id' => $senderId,
                'partner_id' => $partnerId,
                'message_length' => strlen($message)
            ]);

            // 自分自身にはDMを送れない
            if ($senderId === $partnerId) {
                return response()->json(['error' => '自分自身にはDMを送信できません'], 400);
            }

            // ユーザーの存在確認
            $sender = User::find($senderId);
            $partner = User::find($partnerId);
            
            if (!$sender || !$partner) {
                Log::error('User not found', ['sender_id' => $senderId, 'partner_id' => $partnerId]);
                return response()->json(['error' => 'ユーザーが見つかりません'], 404);
            }

            // DMを作成
            $directMessage = DirectMessage::create([
                'sender_id' => $senderId,
                'receiver_id' => $partnerId,
                'content' => $message,
            ]);

            Log::info('DM created successfully', ['dm_id' => $directMessage->id]);

            // 作成されたDMを返す
            $directMessage->load(['sender', 'receiver']);

            return response()->json([
                'message' => 'DMを開始しました',
                'direct_message' => [
                    'id' => $directMessage->id,
                    'content' => $directMessage->content,
                    'created_at' => $directMessage->created_at,
                    'user' => [
                        'id' => $directMessage->sender->id,
                        'name' => $directMessage->sender->name,
                        'avatar' => $directMessage->sender->profile_photo_url,
                    ],
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('DM validation error', ['errors' => $e->errors()]);
            return response()->json(['error' => 'バリデーションエラー', 'details' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error('DM creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'DMの開始に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 特定のユーザーとのダイレクトメッセージ履歴を取得
     */
    public function getConversation(Request $request): JsonResponse
    {
        try {
            $partnerId = $request->query('partner_id');
            
            if (!$partnerId) {
                return response()->json(['error' => 'Partner ID is required'], 400);
            }

            $messages = DirectMessage::where(function ($query) use ($partnerId) {
                $query->where('sender_id', auth()->id())
                      ->where('receiver_id', $partnerId);
            })->orWhere(function ($query) use ($partnerId) {
                $query->where('sender_id', $partnerId)
                      ->where('receiver_id', auth()->id());
            })->with(['sender', 'receiver'])
              ->orderBy('created_at', 'asc')
              ->limit(50)
              ->get();

            $formattedMessages = $messages->map(function ($message) {
                return [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'sender' => [
                        'id' => $message->sender->id,
                        'name' => $message->sender->name,
                        'avatar' => $message->sender->profile_photo_url,
                    ],
                    'receiver' => [
                        'id' => $message->receiver->id,
                        'name' => $message->receiver->name,
                        'avatar' => $message->receiver->profile_photo_url,
                    ],
                    'file_path' => $message->file_path,
                    'file_name' => $message->file_name,
                    'file_mime' => $message->file_mime,
                    'file_size' => $message->file_size,
                    'read_at' => $message->read_at,
                    'time' => $message->created_at->format('g:i A'),
                    'created_at' => $message->created_at,
                ];
            });

            return response()->json($formattedMessages);
        } catch (\Exception $e) {
            Log::error('Failed to get conversation: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to get conversation',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ダイレクトメッセージを送信
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Direct message creation request received', ['data' => $request->all()]);

            $validated = $request->validate([
                'content' => 'nullable|string|max:1000',
                'receiver_id' => 'required|exists:tbl_users,id',
                'file' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx',
            ]);

            // 自分自身には送信できない
            if ($validated['receiver_id'] == auth()->id()) {
                return response()->json(['error' => '自分自身にはメッセージを送信できません'], 422);
            }

            $fileData = [
                'file_path' => null,
                'file_name' => null,
                'file_mime' => null,
                'file_size' => null,
            ];

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $path = $file->store('direct-messages', 'public');
                $fileData = [
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_mime' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ];
            }

            if (empty($validated['content']) && !$request->hasFile('file')) {
                return response()->json(['error' => 'メッセージまたはファイルが必要です'], 422);
            }

            $message = DirectMessage::create([
                'content' => $validated['content'] ?? '',
                'sender_id' => auth()->id(),
                'receiver_id' => $validated['receiver_id'],
                ...$fileData,
            ]);

            $message->load(['sender', 'receiver']);

            Log::info('Direct message created successfully', ['message_id' => $message->id]);

            return response()->json([
                'id' => $message->id,
                'content' => $message->content,
                'sender_id' => $message->sender_id,
                'receiver_id' => $message->receiver_id,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                    'avatar' => $message->sender->profile_photo_url,
                ],
                'receiver' => [
                    'id' => $message->receiver->id,
                    'name' => $message->receiver->name,
                    'avatar' => $message->receiver->profile_photo_url,
                ],
                'file_path' => $message->file_path,
                'file_name' => $message->file_name,
                'file_mime' => $message->file_mime,
                'file_size' => $message->file_size,
                'read_at' => $message->read_at,
                'time' => $message->created_at->format('g:i A'),
                'created_at' => $message->created_at,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Direct message creation failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Direct message creation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 