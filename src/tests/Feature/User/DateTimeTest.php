<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DateTimeTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_現在の日時情報がUIと同じ形式で出力されている()
    {
        // テスト環境ではメール認証をスキップする
        // 本番では EnsureEmailIsVerified ミドルウェアが有効なので、
        // この画面にアクセスするにはメール認証が必要。
        // PHPUnitでの自動テストでは、メール送信や認証を経由できないため無視する。
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $this->actingAs($user,'web');

        $response = $this->get('/user/check-in');

        $response->assertStatus(200);

        $serverTime = now()->format('Y-m-d H:i');
        $response->assertSee($serverTime);

    }

}
