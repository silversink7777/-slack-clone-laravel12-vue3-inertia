<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Message;
use App\Repositories\Interfaces\ChannelRepositoryInterface;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Repositories\Interfaces\DirectMessageRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    protected $channelRepository;
    protected $messageRepository;
    protected $directMessageRepository;

    public function __construct(
        ChannelRepositoryInterface $channelRepository,
        MessageRepositoryInterface $messageRepository,
        DirectMessageRepositoryInterface $directMessageRepository
    ) {
        $this->channelRepository = $channelRepository;
        $this->messageRepository = $messageRepository;
        $this->directMessageRepository = $directMessageRepository;
    }

    public function __invoke(Request $request): Response
    {
        $channels = $this->channelRepository->getByUserId(auth()->id());

        $channelIds = $channels->pluck('id')->toArray();
        $messages = $this->messageRepository->getByUserChannels($channelIds)
            ->map(fn ($message) => [
                'id' => $message->id,
                'user' => [
                    'name' => $message->user->name,
                    'avatar' => $message->user->profile_photo_url,
                ],
                'time' => $message->created_at->format('g:i A'),
                'text' => $message->content,
            ]);

        Log::info('Dashboard __invoke called', [
            'user_id' => auth()->id(),
            'channels_count' => $channels->count(),
            'messages_count' => $messages->count(),
            'channels' => $channels->toArray(),
        ]);

        // ダイレクトメッセージパートナーを取得
        $directMessagePartners = $this->directMessageRepository->getPartners(auth()->id());

        return Inertia::render('Dashboard', [
            'channels' => $channels->map(fn ($channel) => [
                'id' => $channel->id,
                'name' => $channel->name,
            ]),
            'direct-messages' => $directMessagePartners,
            'messages' => $messages,
        ]);
    }

    public function index(Request $request): Response
    {
        $channels = $this->channelRepository->getByUserId(auth()->id());
        $selectedChannelId = $request->query('channel');
        $selectedMessageId = $request->query('message');

        $channelIds = $channels->pluck('id')->toArray();
        $messages = $this->messageRepository->getByUserChannels($channelIds)
            ->map(fn ($message) => [
                'id' => $message->id,
                'user' => [
                    'name' => $message->user->name,
                    'avatar' => $message->user->profile_photo_url,
                ],
                'time' => $message->created_at->format('g:i A'),
                'text' => $message->content,
            ]);

        Log::info('Dashboard index called', [
            'user_id' => auth()->id(),
            'channels_count' => $channels->count(),
            'messages_count' => $messages->count(),
            'channels' => $channels->toArray(),
            'selected_channel_id' => $selectedChannelId,
            'selected_message_id' => $selectedMessageId,
        ]);

        // ダイレクトメッセージパートナーを取得
        $directMessagePartners = $this->directMessageRepository->getPartners(auth()->id());

        return Inertia::render('Dashboard', [
            'channels' => $channels->map(fn ($channel) => [
                'id' => $channel->id,
                'name' => $channel->name,
                'active' => $selectedChannelId && $channel->id == $selectedChannelId,
            ]),
            'direct-messages' => $directMessagePartners,
            'messages' => $messages,
            'selectedChannelId' => $selectedChannelId,
            'selectedMessageId' => $selectedMessageId,
        ]);
    }
}
