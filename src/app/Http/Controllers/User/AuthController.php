<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request)
    {
        //管理者でログイン中なら強制ログアウト
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
            session()->invalidate();
            session()->regenerateToken();
        }

        $request->authenticate();

        return redirect()->intended('/user/check-in');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
