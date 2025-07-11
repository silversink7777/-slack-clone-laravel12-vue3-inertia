<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\DirectMessage;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MessageExportController extends Controller
{
    /**
     * チャンネルメッセージをエクスポート
     */
    public function exportChannelMessages(Request $request): JsonResponse
    {
        try {
            Log::info('Export channel messages started', [
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            $request->validate([
                'channel_id' => 'required|exists:tbl_channels,id',
                'format' => 'required|in:csv,json',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'include_files' => 'boolean',
            ]);

            $channel = Channel::findOrFail($request->channel_id);
            
            // チャンネルメンバーかチェック
            if (!$channel->members()->where('user_id', auth()->id())->exists()) {
                Log::warning('User not member of channel', [
                    'user_id' => auth()->id(),
                    'channel_id' => $request->channel_id
                ]);
                return response()->json(['error' => 'チャンネルにアクセスする権限がありません'], 403);
            }

            $query = Message::where('channel_id', $request->channel_id)
                ->with(['user:id,name,email', 'channel:id,name'])
                ->orderBy('created_at', 'asc');

            // 日付フィルター
            if ($request->start_date) {
                $query->where('created_at', '>=', $request->start_date);
            }
            if ($request->end_date) {
                $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
            }

            $messages = $query->get();

            Log::info('Messages retrieved for export', [
                'channel_id' => $request->channel_id,
                'message_count' => $messages->count()
            ]);

            $exportData = $this->formatMessagesForExport($messages, $request->format, $request->include_files);
            
            $filename = $this->generateFilename($channel->name, $request->format, $request->start_date, $request->end_date);
            
            // ファイルを保存
            $filePath = $this->saveExportFile($exportData, $filename, $request->format);
            
            // エクスポート履歴を記録
            $this->logExportHistory($request->channel_id, null, $request->format, $filename, $messages->count());

            Log::info('Export completed successfully', [
                'filename' => $filename,
                'file_path' => $filePath,
                'message_count' => $messages->count()
            ]);

            return response()->json([
                'success' => true,
                'download_url' => route('api.messages.export.download', ['filename' => $filename]),
                'filename' => $filename,
                'message_count' => $messages->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Export failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'エクスポートに失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ダイレクトメッセージをエクスポート
     */
    public function exportDirectMessages(Request $request): JsonResponse
    {
        $request->validate([
            'partner_id' => 'required|exists:tbl_users,id',
            'format' => 'required|in:csv,json',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'include_files' => 'boolean',
        ]);

        $partner = User::findOrFail($request->partner_id);
        $currentUser = auth()->user();

        // ダイレクトメッセージの存在確認
        $directMessage = DirectMessage::where(function ($query) use ($currentUser, $partner) {
            $query->where('sender_id', $currentUser->id)
                  ->where('receiver_id', $partner->id);
        })->orWhere(function ($query) use ($currentUser, $partner) {
            $query->where('sender_id', $partner->id)
                  ->where('receiver_id', $currentUser->id);
        })->first();

        if (!$directMessage) {
            return response()->json(['error' => 'ダイレクトメッセージが見つかりません'], 404);
        }

        $query = DirectMessage::where(function ($query) use ($currentUser, $partner) {
            $query->where('sender_id', $currentUser->id)
                  ->where('receiver_id', $partner->id);
        })->orWhere(function ($query) use ($currentUser, $partner) {
            $query->where('sender_id', $partner->id)
                  ->where('receiver_id', $currentUser->id);
        })->with(['sender:id,name,email', 'receiver:id,name,email'])
          ->orderBy('created_at', 'asc');

        // 日付フィルター
        if ($request->start_date) {
            $query->where('created_at', '>=', $request->start_date);
        }
        if ($request->end_date) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        $messages = $query->get();

        $exportData = $this->formatDirectMessagesForExport($messages, $request->format, $request->include_files);
        
        $filename = $this->generateDirectMessageFilename($partner->name, $request->format, $request->start_date, $request->end_date);
        
        // ファイルを保存
        $filePath = $this->saveExportFile($exportData, $filename, $request->format);
        
        // エクスポート履歴を記録
        $this->logExportHistory(null, $request->partner_id, $request->format, $filename, $messages->count());

        return response()->json([
            'success' => true,
            'download_url' => route('api.messages.export.download', ['filename' => $filename]),
            'filename' => $filename,
            'message_count' => $messages->count(),
        ]);
    }

    /**
     * エクスポートファイルをダウンロード
     */
    public function downloadExport(Request $request, string $filename): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $filePath = 'exports/' . $filename;
        
        if (!Storage::exists($filePath)) {
            abort(404, 'ファイルが見つかりません');
        }

        // ファイルの所有者チェック（セキュリティ）
        $exportHistory = \App\Models\MessageExportHistory::where('filename', $filename)
            ->where('user_id', auth()->id())
            ->first();

        if (!$exportHistory) {
            abort(403, 'このファイルにアクセスする権限がありません');
        }

        return Storage::download($filePath, $filename);
    }

    /**
     * エクスポート履歴を取得
     */
    public function getExportHistory(): JsonResponse
    {
        $history = \App\Models\MessageExportHistory::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * メッセージをエクスポート用にフォーマット
     */
    private function formatMessagesForExport($messages, string $format, bool $includeFiles): array
    {
        $formattedMessages = [];

        foreach ($messages as $message) {
            $messageData = [
                'id' => $message->id,
                'content' => $message->content,
                'sender_name' => $message->user->name,
                'sender_email' => $message->user->email,
                'channel_name' => $message->channel->name,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $message->updated_at->format('Y-m-d H:i:s'),
                'is_pinned' => $message->is_pinned ? 'はい' : 'いいえ',
            ];

            if ($includeFiles && $message->file_path) {
                $messageData['file_name'] = $message->file_name;
                $messageData['file_path'] = $message->file_path;
            }

            $formattedMessages[] = $messageData;
        }

        return $formattedMessages;
    }

    /**
     * ダイレクトメッセージをエクスポート用にフォーマット
     */
    private function formatDirectMessagesForExport($messages, string $format, bool $includeFiles): array
    {
        $formattedMessages = [];

        foreach ($messages as $message) {
            $messageData = [
                'id' => $message->id,
                'content' => $message->content,
                'sender_name' => $message->sender->name,
                'sender_email' => $message->sender->email,
                'receiver_name' => $message->receiver->name,
                'receiver_email' => $message->receiver->email,
                'created_at' => $message->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $message->updated_at->format('Y-m-d H:i:s'),
            ];

            if ($includeFiles && $message->file_path) {
                $messageData['file_name'] = $message->file_name;
                $messageData['file_path'] = $message->file_path;
            }

            $formattedMessages[] = $messageData;
        }

        return $formattedMessages;
    }

    /**
     * ファイル名を生成
     */
    private function generateFilename(string $channelName, string $format, ?string $startDate, ?string $endDate): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $dateRange = '';
        
        if ($startDate && $endDate) {
            $dateRange = '_' . $startDate . '_to_' . $endDate;
        } elseif ($startDate) {
            $dateRange = '_from_' . $startDate;
        } elseif ($endDate) {
            $dateRange = '_until_' . $endDate;
        }

        $safeChannelName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $channelName);
        
        return "channel_{$safeChannelName}_messages{$dateRange}_{$timestamp}.{$format}";
    }

    /**
     * ダイレクトメッセージ用ファイル名を生成
     */
    private function generateDirectMessageFilename(string $partnerName, string $format, ?string $startDate, ?string $endDate): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $dateRange = '';
        
        if ($startDate && $endDate) {
            $dateRange = '_' . $startDate . '_to_' . $endDate;
        } elseif ($startDate) {
            $dateRange = '_from_' . $startDate;
        } elseif ($endDate) {
            $dateRange = '_until_' . $endDate;
        }

        $safePartnerName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $partnerName);
        
        return "dm_{$safePartnerName}_messages{$dateRange}_{$timestamp}.{$format}";
    }

    /**
     * エクスポートファイルを保存
     */
    private function saveExportFile(array $data, string $filename, string $format): string
    {
        try {
            $filePath = 'exports/' . $filename;
            
            // exportsディレクトリが存在しない場合は作成
            if (!Storage::exists('exports')) {
                Storage::makeDirectory('exports');
            }
            
            if ($format === 'csv') {
                $csvContent = $this->arrayToCsv($data);
                Storage::put($filePath, $csvContent);
            } else {
                $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                Storage::put($filePath, $jsonContent);
            }

            Log::info('Export file saved', [
                'file_path' => $filePath,
                'format' => $format,
                'data_count' => count($data)
            ]);

            return $filePath;
        } catch (\Exception $e) {
            Log::error('Failed to save export file', [
                'filename' => $filename,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 配列をCSVに変換
     */
    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        
        // ヘッダー行を追加
        fputcsv($output, array_keys($data[0]));
        
        // データ行を追加
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * エクスポート履歴を記録
     */
    private function logExportHistory(?int $channelId, ?int $partnerId, string $format, string $filename, int $messageCount): void
    {
        try {
            $filePath = 'exports/' . $filename;
            $fileSize = Storage::exists($filePath) ? Storage::size($filePath) : null;
            
            \App\Models\MessageExportHistory::create([
                'user_id' => auth()->id(),
                'channel_id' => $channelId,
                'partner_id' => $partnerId,
                'format' => $format,
                'filename' => $filename,
                'message_count' => $messageCount,
                'file_size' => $fileSize,
                'exported_at' => now(),
            ]);
            
            Log::info('Export history recorded', [
                'user_id' => auth()->id(),
                'filename' => $filename,
                'message_count' => $messageCount,
                'file_size' => $fileSize
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record export history', [
                'user_id' => auth()->id(),
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            // 履歴記録の失敗はエクスポート自体の失敗にはしない
        }
    }
} 