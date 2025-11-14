<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Providers\RouteServiceProvider;

class AdminAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request)
    {
        // 一般ユーザーでログイン中なら強制ログアウト
        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
            session()->invalidate();
            session()->regenerateToken();
        }

        $request->authenticate();

        return redirect()->intended(RouteServiceProvider::ADMIN_HOME);
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
