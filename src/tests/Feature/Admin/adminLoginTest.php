<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class adminLoginTest extends TestCase
{

    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_メールアドレスが未入力の場合、バリデーション表示()
    {
        Admin::create([
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);

        $response = $this->post('admin/login', [
            'email' => '',
            'password' => 'pass1234',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));
    }

    public function test_パスワードが未入力の場合、バリデーション表示()
    {
        Admin::create([
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    public function test_登録内容と一致しない場合、バリデーション表示()
    {
        Admin::create([
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'testuser@example.com',
            'password' => 'pass123s',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('email');

        $errors = session('errors');
        $this->assertEquals('ログイン情報が登録されていません', $errors->first('email'));
    }
}
