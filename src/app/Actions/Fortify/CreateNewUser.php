<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Illuminate\Support\Facades\Auth;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function create(array $input)
    {

        //新規登録にRegisterRequestは用いられないため、こちらでバリデーションを実装する必要あり
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'string', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'password_confirmation' => ['required', 'string', 'min:8'],
        ], [
            'name.required' => 'お名前を入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスはメール形式で入力してください',
            'email.unique' => 'このメールアドレスはすでに登録されています',
            'password.required' => 'パスワードを入力してください',
            'password.min' => 'パスワードは８字以上で入力してください',
            'password_confirmation.required' => '確認用パスワードを入力してください',
            'password_confirmation.min' => 'パスワードは８字以上で入力してください',
        ]);

        // パスワード一致チェック
        $validator->after(function ($validator) use ($input) {
            if (($input['password'] ?? null) !== ($input['password_confirmation'] ?? null)) {
                $validator->errors()->add('password', 'パスワードと一致しません');
            }
        });

        $data = $validator->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        //メール二通届くため一旦消す　event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}