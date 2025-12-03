<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;


class EmailAuthenticationTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_会員登録後にメール認証通知が送信される()
    {
        Notification::fake();

        $user = User::create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
            'email_verified_at' => null,
        ]);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_認証誘導画面にアクセスできる()
    {

        $user = User::create([
            'name' => '未認証太郎',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
        $response->assertSee('登録していただいたメールアドレスに認証メールを送付しました');
        $response->assertSee('認証はこちらから');
    }
    public function test_メール認証完了後に勤怠登録画面に遷移する()
    {
        $user = User::create([
            'name' => '未認証太郎',
            'email' => 'unverified2@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect(route('user.check-in'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_認証メール再送信()
    {
        Notification::fake();

        $user = User::create([
            'name' => '再送信太郎',
            'email' => 'resend@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => null,
        ]);

        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
