<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\RegisterResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {

        $this->app->singleton(
            \Laravel\Fortify\Contracts\LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        $this->app->singleton(
            \Laravel\Fortify\Contracts\RegisterResponse::class,
            \App\Http\Responses\RegisterResponse::class
        );


    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Fortify::loginView(function() {
            return view('auth.login');
        });

        Fortify::registerView(function() {
            return view('auth.register');
        });

        Fortify::loginView(function () {
            if(request()->is('admin/*')) {
                return view('admin.auth.login');
            }
        });

        Fortify::createUsersUsing(CreateNewUser::class);
    }
}
