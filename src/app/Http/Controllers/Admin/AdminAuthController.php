<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->authenticate();

        return redirect()->intended(RouteServiceProvider::ADMIN_HOME);
    }

    public function logout()
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login');
    }
}
