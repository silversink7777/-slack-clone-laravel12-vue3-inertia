<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\PasswordValidationRules;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Inertia\Inertia;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // 登録完了後のリダイレクト処理
        Fortify::registerView(function () {
            return Inertia::render('Auth/Register');
        });

        // 登録完了後の処理
        Fortify::registerView(function (Request $request) {
            // 招待IDが含まれている場合の処理
            $invitationId = $request->get('invitation');
            $email = $request->get('email');
            
            return Inertia::render('Auth/Register', [
                'invitation_id' => $invitationId,
                'email' => $email,
            ]);
        });

        // 登録完了後のリダイレクト
        Fortify::redirects('register', function (Request $request) {
            // 招待から登録した場合は、招待通知ページにリダイレクト
            if ($request->has('invitation_id')) {
                return redirect()->route('invitations', [
                    'invitation' => $request->invitation_id,
                    'registered' => 'true'
                ]);
            }
            
            // 通常の登録の場合はダッシュボードにリダイレクト
            return redirect()->intended(self::HOME);
        });
    }
}
