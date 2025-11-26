<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_休憩ボタンが正しく機能する()
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

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'working',
        ]);

        $response = $this->get('/user/check-in');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        $this->post('/user/break-start')->assertStatus(302);

        //DB作成された確認
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
        ]);

        $break = \App\Models\BreakTime::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($break->break_start);

        $response = $this->get('/user/check-in');
        $response->assertSee('休憩中');
    }

    public function test_休憩入と休憩戻が正しく機能し、何回でも操作できる()
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

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status' => 'working',
        ]);


        //休憩入ボタン表示
        $response = $this->get('/user/check-in');
        $response->assertStatus(200);
        $response->assertSee('休憩入');

        //休憩開始
        $this->post('/user/break-start')->assertStatus(302);

        //DBに休憩開始が記録されているか
        $break = BreakTime::where('attendance_id', $attendance->id)->latest()->first();

        //画面でステータスが休憩中に変わっているか確認
        $response = $this->get('/user/check-in');
        $response->assertSee('休憩中');

        //休憩戻るボタン押す
        $this->post('/user/break-end')->assertStatus(302);

        //DBに休憩終了が記録されているか確認
        $break->refresh();
        $this->assertNotNull($break->break_end);

        //画面でステータスが出勤中に戻っているか
        $response = $this->get('/user/check-in');
        $response->assertSee('出勤中');

        //再度休憩入りして休憩中が表示されるか
        $this->post('/user/break-start')->assertStatus(302);
        $response = $this->get('/user/check-in');
        $response->assertSee('休憩中');

        //DBに新しい休憩が作成されているか確認
        $breaks = BreakTime::where('attendance_id', $attendance->id )->get();
        $this->assertCount(2, $breaks);

    }

    public function test_休憩時刻が勤怠一覧画面で確認できる()
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

        //出勤時刻を固定
        Carbon::setTestNow('2025-01-01 09:00');
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'status'=> 'working',
        ]);

        //休憩開始を固定
        Carbon::setTestNow('2025-01-01 10:00');
        $this->post('/user/break-start')->assertStatus(302);
        $break = BreakTime::where('attendance_id', $attendance->id)->latest()->first();
        $this->assertEquals('10:00', $break->break_start->format('H:i'));

        //休憩終了を固定
        Carbon::setTestNow('2025-01-01 10:30');
        $this->post('/user/break-end')->assertStatus(302);
        $break->refresh();
        $this->assertEquals('10:30', $break->break_end->format('H:i'));

        //勤怠一覧画面で表示確認
        $response = $this->get('/attendance')->assertStatus(200);
        $response->assertSee($attendance->break_hours_formatted);

        Carbon::setTestNow();
    }
}
