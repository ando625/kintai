<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use Carbon\Carbon;

class CheckInTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_出勤ボタンが正しく機能する()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
            'status' => 'off_duty',
        ]);
        //ログイン
        $this->actingAs($user, 'web');

        //出勤画面アクセス
        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        //出勤処理実行
        $this->post('/user/clock-in');

        //DBにworkingが保存されたか確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'working',
        ]);

        //表示も確認
        $response = $this->get('/user/check-in');
        $response->assertSee('出勤中');
    }

    public function test_出勤は一日一回のみできる()
    {

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        //ログイン
        $this->actingAs($user, 'web');

        //退勤済の勤怠記録を作成
        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
            'status' => 'finished',
        ]);

        //ページアクセス
        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        /*出勤ボタンが表示されないことを確認
        bladeではボタンは表示されず、かわりに「おつかれさまでした。」が表示される */
        $response->assertSee('お疲れ様でした。');
        $response->assertDontSee('<button>出勤</button>');

    }

    public function test_出勤時刻が勤怠一覧画面で確認できる()
    {
        //時間を固定
        $fixedTime = Carbon::create(2025,1,1,9,0,0);
        Carbon::setTestNow($fixedTime);

        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
            'status' => 'off_duty',
        ]);
        //ログイン
        $this->actingAs($user, 'web');

        //出勤処理実行
        $this->post('/user/clock-in')->assertStatus(302);

        //出勤後のデータ取得
        $attendance = Attendance::where('user_id', $user->id)->first();

        //勤怠一覧ページアクセス
        $response = $this->get('/attendance')->assertStatus(200);

        //表示確認
        $displayDate = $attendance->work_date->format('m/d'); // 月/日
        $weekday = ['日', '月', '火', '水', '木', '金', '土'][$attendance->work_date->dayOfWeek];
        $response->assertSee("{$displayDate}({$weekday})");
        $response->assertSee($attendance->clock_in->format('H:i'));

        // Carbon の時間を元に戻す
        Carbon::setTestNow();
    }
}
