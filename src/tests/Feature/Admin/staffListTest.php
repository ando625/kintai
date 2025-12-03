<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\BreakTime;

class staffListTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_管理者が全ユーザーの氏名とメールアドレスを確認できる()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $user1 = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);
        $user2 = User::create([
            'name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->get('/admin/staff/list');
        $response->assertStatus(200);

        $response->assertSee('ユーザー1');
        $response->assertSee('user1@example.com');
        $response->assertSee('ユーザー2');
        $response->assertSee('user2@example.com');
    }

    public function test_ユーザーの勤怠情報が正しく表示される()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $user = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-12-05',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}");
        $response->assertStatus(200);

        $response->assertSee('ユーザー1さんの勤怠');
        $response->assertSee('12/05');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_前月の勤怠情報が表示される()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $user = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);

        $currentMonthAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-11-20',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $currentMonthAttendance->id,
            'break_start' => '12:00',
            'break_end'   => '13:00',
        ]);
        $prevMonthAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-10-15',
            'clock_in' => '10:00',
            'clock_out' => '19:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $prevMonthAttendance->id,
            'break_start' => '12:00',
            'break_end'   => '13:00',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}?month=2025-10");
        $response->assertStatus(200);

        $response->assertSee('2025/10');
        $response->assertSee('10/15');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('1:00');
        $response->assertDontSee('2025/11');
    }

    public function test_翌月の勤怠情報が表示される()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $user = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);

        $nextMonthAttendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-12-05',
            'clock_in' => '08:30',
            'clock_out' => '17:30',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $nextMonthAttendance->id,
            'break_start' => '12:00',
            'break_end'   => '12:30',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}?month=2025-12");
        $response->assertStatus(200);

        $response->assertSee('2025/12');
        $response->assertSee('12/05');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
        $response->assertSee('0:30');
        $response->assertDontSee('2025/11');
    }

    public function test_勤怠詳細画面に遷移できる()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $user = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-11-20',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        $response = $this->get("/admin/attendance/staff/{$user->id}?month=2025-11");
        $response->assertStatus(200);
        $response->assertSee("/admin/attendance/{$attendance->id}");
        $response->assertStatus(200);
        $detailResponse = $this->get("/admin/attendance/{$attendance->id}");
        $detailResponse->assertStatus(200);

        $detailResponse->assertSee('2025年');
        $detailResponse->assertSee('11月20日');
        $detailResponse->assertSee('09:00');
        $detailResponse->assertSee('18:00');
        $detailResponse->assertSee('12:00');
        $detailResponse->assertSee('13:00');
    }
}
