<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\ChannelInvitationRepositoryInterface;
use App\Repositories\ChannelInvitationRepository;
use App\Repositories\Interfaces\ChannelMemberRepositoryInterface;
use App\Repositories\ChannelMemberRepository;
use App\Repositories\Interfaces\ChannelRepositoryInterface;
use App\Repositories\ChannelRepository;
use App\Repositories\Interfaces\MessageRepositoryInterface;
use App\Repositories\MessageRepository;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\Interfaces\UserOnlineStatusRepositoryInterface;
use App\Repositories\UserOnlineStatusRepository;
use App\Repositories\Interfaces\DirectMessageRepositoryInterface;
use App\Repositories\DirectMessageRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // リポジトリの依存性注入を設定
        $this->app->bind(ChannelInvitationRepositoryInterface::class, ChannelInvitationRepository::class);
        $this->app->bind(ChannelMemberRepositoryInterface::class, ChannelMemberRepository::class);
        $this->app->bind(ChannelRepositoryInterface::class, ChannelRepository::class);
        $this->app->bind(MessageRepositoryInterface::class, MessageRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserOnlineStatusRepositoryInterface::class, UserOnlineStatusRepository::class);
        $this->app->bind(DirectMessageRepositoryInterface::class, DirectMessageRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // morphMapの設定
        Relation::morphMap([
            'users' => \App\Models\User::class,
        ]);
    }
}
