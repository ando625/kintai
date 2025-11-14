<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Illuminate\Support\Facades\Auth; 


class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        // メール未認証ならメール認証ページへ
        $user = $request->user();
        if (! $user->hasVerifiedEmail()) {
            // ログアウトしない → ログイン済みのままメール認証ページへ
            return redirect()->route('verification.notice');
        }

        // 認証済みなら勤怠画面へ
        return redirect()->intended(route('user.check-in'));
    }
}
