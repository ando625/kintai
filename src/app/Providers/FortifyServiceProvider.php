<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\VerifyEmailViewResponse;
use Laravel\Fortify\Contracts\VerifyEmailViewResponse as VerifyEmailViewResponseContract;
use App\Http\Responses\RegisterResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Fortify のルートは boot() で、routes/web.php より後に登録する（下の registerFortifyRoutes を参照）
        Fortify::ignoreRoutes();

        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);

        $this->app->singleton(VerifyEmailViewResponseContract::class, VerifyEmailViewResponse::class);

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        /*管理者と一般ログインは自作コントローラ（FormRequest使用）でログイン
        App\Http\Controllers\Admin\AdminAuthController（自分でセキュリティも設定済み）
        App\Http\Controllers\User\AuthController（自分でセキュリティも設定済み）*/


        Fortify::registerView(function () {
            if (auth()->check()) {
                auth()->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }
            return view('auth.register');
        });

        Fortify::createUsersUsing(CreateNewUser::class);

        $this->registerFortifyRoutes();

        // config/fortify.php の limiters で指定している名前の回数制限。
        // Laravel 11 からは、定義されていない名前を指定するとエラーになるため定義する
        // （Fortify の /user/login と /user/two-factor-challenge で使われる）
        RateLimiter::for('user/login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

    }

    /**
     * Fortify のルートを登録する（Fortify が自分で登録するときと同じ設定）。
     *
     * routes/web.php の verification.verify・verification.send は、Fortify のルートと同じ名前。
     * Laravel 12 からは同じ名前のルートがあると「先に登録したほう」が使われるため、
     * Fortify のルートを web.php より後に登録し、これまでどおり web.php 側が使われるようにする。
     * （このプロバイダは config/app.php で RouteServiceProvider の後にあるため、web.php より後になる）
     */
    private function registerFortifyRoutes(): void
    {
        Route::group([
            'namespace' => 'Laravel\Fortify\Http\Controllers',
            'domain' => config('fortify.domain', null),
            'prefix' => config('fortify.prefix'),
        ], function () {
            $this->loadRoutesFrom(dirname((new \ReflectionClass(Fortify::class))->getFileName()).'/../routes/routes.php');
        });
    }
}
