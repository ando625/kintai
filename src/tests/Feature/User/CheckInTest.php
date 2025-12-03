<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
            'status' => 'off_duty',
        ]);
        $this->actingAs($user, 'web');

        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        $this->post('/user/clock-in');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 'working',
        ]);

        $response = $this->get('/user/check-in');
        $response->assertSee('出勤中');
    }

    public function test_出勤は一日一回のみできる()
    {
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
            'clock_out' => now(),
            'status' => 'finished',
        ]);

        $response = $this->get('/user/check-in');
        $response->assertStatus(200);

        $response->assertSee('お疲れ様でした。');
        $response->assertDontSee('<button>出勤</button>');

    }

    public function test_出勤時刻が勤怠一覧画面で確認できる()
    {
        $fixedTime = Carbon::create(2025,1,1,9,0,0);
        Carbon::setTestNow($fixedTime);

        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'test太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
            'status' => 'off_duty',
        ]);
        $this->actingAs($user, 'web');

        $this->post('/user/clock-in')->assertStatus(302);

        $attendance = Attendance::where('user_id', $user->id)->first();

        $response = $this->get('/attendance')->assertStatus(200);

        $displayDate = $attendance->work_date->format('m/d');
        $weekday = ['日', '月', '火', '水', '木', '金', '土'][$attendance->work_date->dayOfWeek];
        $response->assertSee("{$displayDate}({$weekday})");
        $response->assertSee($attendance->clock_in->format('H:i'));

        Carbon::setTestNow();
    }
}
