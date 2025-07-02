<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Channel;
use App\Repositories\Interfaces\MessageRepositoryInterface;

class MessageManagementController extends Controller
{
    protected $messageRepository;

    public function __construct(MessageRepositoryInterface $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public function index(Request $request)
    {
        $filters = $request->only(['search', 'user_id', 'channel_id']);
        $messages = $this->messageRepository->searchWithFilters($filters, 20);
        $users = User::select('id', 'name')->get();
        $channels = Channel::select('id', 'name')->get();
        return Inertia::render('Admin/Messages/Index', [
            'messages' => $messages,
            'users' => $users,
            'channels' => $channels,
            'filters' => $filters
        ]);
    }

    public function show($id)
    {
        $message = $this->messageRepository->findByIdWithRelations($id, ['user', 'channel']);
        if (!$message) {
            abort(404);
        }
        return Inertia::render('Admin/Messages/Show', [
            'message' => $message
        ]);
    }

    public function destroy($id)
    {
        $message = $this->messageRepository->findById($id);
        if (!$message) {
            abort(404);
        }
        $this->messageRepository->delete($message);
        return redirect()->route('admin.messages.index')->with('success', 'メッセージを削除しました');
    }
} 