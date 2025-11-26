<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */


    public function test_名前が未入力の場合、バリデーション表示()
    {

        $response = $this->post('/user/register',  [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('name');

        $errors = session('errors');
        $this->assertEquals('お名前を入力してください', $errors->first('name'));
    }

    public function test_メールアドレスが未入力の場合、バリデーション表示()
    {

        $response = $this->post('user/register', [
            'name' => 'test太郎',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        //リダイレクトされる = バリデーションが発動している証拠
        $response->assertStatus(302);
        //セッション（=一時的に保存される情報）にemail のエラーが入っているか確認。
        $response->assertSessionHasErrors('email');

        //Laravel のレスポンス $response だけではエラーメッセージの中身までは見られないから。セッションに保存された errors を自分で取り出す必要がある。
        $errors = session('errors');

        //email に対する最初のエラーメッセージを取り出している。
        $this->assertEquals('メールアドレスを入力してください', $errors->first('email'));

    }

    public function test_パスワードが8文字未満の場合、バリデーション表示()
    {
        $response = $this->post('/user/register', [
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'pass',
            'password_confirmation' => 'pass',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードは8文字以上で入力してください', $errors->first('password'));
    }

    public function test_パスワードが一致しない場合、バリデーション表示()
    {
        $response = $this->post('/user/register', [
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードと一致しません', $errors->first('password'));

    }

    public function test_パスワードが未入力の場合、バリデーション表示()
    {
        $response = $this->post('/user/register', [
            'name' => 'test太郎',
            'email' =>'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('password');

        $errors = session('errors');
        $this->assertEquals('パスワードを入力してください', $errors->first('password'));
    }

    public function test_formに内容が入力されている場合、ユーザー情報が保存される()
    {
        $response = $this->post('/user/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'pass1234',
            'password_confirmation' => 'pass1234',
        ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);


    }
}
