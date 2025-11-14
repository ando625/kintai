<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\VerifyEmailViewResponse;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse as VerifyEmailViewResponseContract;
use App\Http\Responses\RegisterResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

        // メール認証ページ表示用
        $this->app->singleton(VerifyEmailViewResponseContract::class, VerifyEmailViewResponse::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // 開発中は制限なし
        if (app()->environment('local')) {
            RateLimiter::for('login', function (Request $request) {
                return Limit::none(); // 無制限
            });
        }


        /*管理者と一般ログインは自作コントローラ（FormRequest使用）でログイン
        App\Http\Controllers\Admin\AdminAuthController（自分でセキュリティも設定済み）
        App\Http\Controllers\User\AuthController（自分でセキュリティも設定済み）*/


        // 🌟 会員登録
        Fortify::registerView(function () {
            // 登録画面アクセス時にログアウト
            if (auth()->check()) {
                auth()->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }
            return view('auth.register');
        });

        Fortify::createUsersUsing(CreateNewUser::class);


    }
}
