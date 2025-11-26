<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    protected function createUserAndLogin()
    {
        //メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');
        return $user;
    }

    public function test_自分が行った勤怠情報が全て表示されている()
    {
        $user = $this->createUserAndLogin();

        //勤怠を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-01-10',
            'clock_in' => '2025-01-10 09:00:00',
            'clock_out' => '2025-01-10 18:00:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-01-10 12:00:00',
            'break_end' => '2025-01-10 13:00:00',
        ]);

        $response = $this->get('/attendance?month=2025-01');

        $response->assertStatus(200);
        $response->assertSee($attendance->work_date->translatedFormat('m/d'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    public function test_現在の月が表示される()
    {
        $user = $this->createUserAndLogin();

        $response =$this->get('/attendance?month=2025-11');
        $response->assertStatus(200);
        $response->assertSee('2025/11');

    }

    public function test_前月ボタンで前月勤怠情報が表示される()
    {
        $user = $this->createUserAndLogin();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-10-15',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-10-15 12:00:00',
            'break_end' => '2025-10-15 13:00:00',
        ]);

        $response = $this->get('/attendance?month=2025-10');

        $response->assertStatus(200);
        $response->assertSee('10/15');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }

    /**
     * 翌月ボタンで翌月勤怠情報が表示される
     */
    public function test_翌月ボタンで翌月勤怠情報が表示される()
    {
        $user = $this->createUserAndLogin();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-12-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-12-05 12:00:00',
            'break_end' => '2025-12-05 13:00:00',
        ]);

        $response = $this->get('/attendance?month=2025-12');

        $response->assertStatus(200);
        $response->assertSee('12/05');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
    }


    public function test_詳細ボタンを押すと勤怠詳細画面に遷移する()
    {
        $user = $this->createUserAndLogin();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-11-24',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-11-24 12:00:00',
            'break_end' => '2025-11-24 13:00',
        ]);

        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee('2025年');
        $response->assertSee('11月24日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
