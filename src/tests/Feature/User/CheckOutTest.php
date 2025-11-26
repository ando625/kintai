<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use Carbon\Carbon;

class CheckOutTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_退勤ボタンが正しく機能する()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        //ユーザー作成
        $user = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'working',
        ]);

        //勤怠画面アクセス
        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        //退勤ボタンが表示されてるかどうか
        $response->assertSee('退勤');

        //退勤処理実行
        $this->post('/user/clock-out')->assertStatus(302);

        //DBに勤怠済が保存されたか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'finished',
        ]);
        // 退勤後のデータ取得
        $attendance = Attendance::where('user_id', $user->id)->first();

        //表示も確認
        $response = $this->get('/user/check-in');
        $response->assertSee('退勤済');

        $clockInFormatted = $attendance->clock_in->format('H:i');
        $response->assertSee($clockInFormatted);

    }

    public function test_退勤時刻が勤怠一覧画面で確認できる()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        //ユーザー作成
        $user = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        Carbon::setTestNow('2025-01-01 10:00');
        $this->post('/user/clock-in');
        Carbon::setTestNow('2025-01-01 19:00');

        $this->post('/user/clock-out');

        $response = $this->get('/attendance');
        $response->assertSee('19:00');
    }
}
