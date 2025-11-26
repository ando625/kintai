<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\BreakTimeRequest;
use Carbon\Carbon;

class AttendanceIndexTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_管理者は当日の日付と全ユーザーの勤怠一覧を確認できる()
    {

        Carbon::setTestNow('2025-11-25');
        $today = Carbon::today();
        $date = $today->toDateString(); // '2025-11-25'

        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');


        $user1 = User::create([
            'name' => 'テスト1',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $user2 = User::create([
            'name' => 'テスト2',
            'email' => 'test2@example.com',
            'password' => Hash::make('password'),
        ]);

        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => $date,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '10:00',
            'break_end'   => '10:15',
        ]);
        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => $date,
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start' => '14:00',
            'break_end'   => '15:00',
        ]);

        $response = $this->get("/admin/index?date={$date}");

        $response->assertStatus(200);
        $response->assertSee('2025年11月25日');
        $response->assertSee('テスト1');
        $response->assertSee('テスト2');
        $response->assertSee('09:00');
        $response->assertSee('10:00');
        $response->assertSee('18:00');
        $response->assertSee('19:00');
        $response->assertSee('0:15');
        $response->assertSee('1:00');
    }

    public function test_前日と翌日ボタンで正しい勤怠が表示される()
    {

        Carbon::setTestNow('2025-11-20');
        $todayDate = Carbon::today();
        $prevDate = $todayDate->copy()->subDay();
        $nextDate = $todayDate->copy()->addDay();

        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');



        // ===== ユーザー作成 =====
        $user1 = User::create([
            'name' => 'テスト1',
            'email' => 'test1@example.com',
            'password' => Hash::make('password'),
        ]);
        $user2 = User::create([
            'name' => 'テスト2',
            'email' => 'test2@example.com',
            'password' => Hash::make('password'),
        ]);

        // ===== 勤怠データ作成 =====
        $attendance1 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => $prevDate->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance1->id,
            'break_start' => '10:00',
            'break_end'   => '11:00',
        ]);

        $attendance2 = Attendance::create([
            'user_id' => $user2->id,
            'work_date' => $nextDate->toDateString(),
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance2->id,
            'break_start' => '11:00',
            'break_end'   => '12:15',
        ]);

        $attendance3 = Attendance::create([
            'user_id' => $user1->id,
            'work_date' => $todayDate->toDateString(),
            'clock_in' => '09:30',
            'clock_out' => '18:30',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance3->id,
            'break_start' => '10:00',
            'break_end'   => '10:15',
        ]);

        $responseToday = $this->get("/admin/index?date={$todayDate->toDateString()}");
        $responseToday->assertStatus(200);
        $responseToday->assertSee('2025年11月20日');
        $responseToday->assertSee('テスト1');
        $responseToday->assertSee('09:30');
        $responseToday->assertSee('18:30');
        $responseToday->assertSee('0:15');

        $responsePrev = $this->get("/admin/index?date={$prevDate->toDateString()}");
        $responsePrev->assertStatus(200);
        $responsePrev->assertSee('2025年11月19日');
        $responsePrev->assertSee('09:00');
        $responsePrev->assertSee('18:00');
        $responsePrev->assertSee('1:00');

        $responseNext = $this->get("/admin/index?date={$nextDate->toDateString()}");
        $responseNext->assertStatus(200);
        $responseNext->assertSee('2025年11月21日');
        $responseNext->assertSee('10:00');
        $responseNext->assertSee('19:00');
        $responseNext->assertSee('1:15');
    }
}
