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

class AttendanceRequestTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_承認待ちの修正申請が全て表示されている()
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

        // 勤怠データ
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-11-20',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);

        // 休憩データ
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00',
            'break_end'   => '13:00',
        ]);

        // 修正申請（承認待ち）
        $pendingRequest = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'after_clock_in' => '09:30',
            'after_clock_out' => '18:30',
            'after_remarks' => '遅刻対応',
        ]);

        $response = $this->get('/admin/requests?tab=pending');
        $response->assertSee('承認待ち');
        $response->assertSee('ユーザー1');
        $response->assertSee('遅刻対応');
    }

    public function test_修正申請の承認処理で勤怠が更新される()
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
            'work_date' => '2025-11-21',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);

        $request = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'status' => 'pending',
            'after_clock_in' => '08:30',
            'after_clock_out' => '17:30',
            'after_remarks' => '早出調整',
        ]);

        // 管理者が承認
        $request->update(['status' => 'approved']);
        $attendance->update([
            'clock_in' => $request->after_clock_in,
            'clock_out' => $request->after_clock_out,
        ]);

        $response = $this->get('/admin/requests?tab=approved');
        $response->assertSee('承認済み');
        $response->assertSee('早出調整');
    }

    public function test_修正申請の詳細内容が正しく表示されている()
    {
        // 管理者ログイン
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        // 一般ユーザー作成
        $user = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);

        // 勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-11-20',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00',
            'break_end'   => '13:00',
        ]);

        // 修正申請作成
        $request = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'after_clock_in' => '08:30:00',
            'after_clock_out' => '17:30:00',
            'after_remarks' => '遅刻対応',
            'status' => 'pending',
        ]);

        // 修正後の休憩時間作成
        BreakTimeRequest::create([
            'attendance_request_id' => $request->id,
            'after_start' => '12:00:00',
            'after_end' => '12:30:00',
        ]);

        $response = $this->get("/admin/requests/approve/{$request->id}");
        $response->assertStatus(200);

        // 内容が正しく表示されているか確認
        $response->assertSee($user->name);
        $response->assertSee('08:30');
        $response->assertSee('17:30');
        $response->assertSee('遅刻対応');
        $response->assertSee('12:00');
        $response->assertSee('12:30');
    }

    public function test_修正申請の承認処理が正しく行われる()
    {
        // 管理者ログイン
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        // 一般ユーザー作成
        $user = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
        ]);

        $workDate = '2025-11-20';

        // 勤怠データ作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => $workDate,
            'clock_in' => $workDate.' 09:00:00',
            'clock_out' => $workDate.' 18:00:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => $workDate.' 12:00',
            'break_end'   => $workDate.' 13:00',
        ]);

        // 修正申請（承認待ち）
        $request = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'after_clock_in' => $workDate.' 08:30:00',
            'after_clock_out' => $workDate.' 17:30:00',
            'after_remarks' => '遅刻対応',
            'status' => 'pending',
        ]);

        BreakTimeRequest::create([
            'attendance_request_id' => $request->id,
            'after_start' => $workDate.' 12:00:00',
            'after_end' => $workDate.' 12:30:00',
        ]);

        $this->patch("/admin/requests/approve/{$request->id}");

        $this->assertDatabaseHas('attendance_requests', [
            'id' => $request->id,
            'status' => 'approved',
        ]);

        // 勤怠情報も修正後の時間に更新されていることを確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => $workDate.' 08:30:00',
            'clock_out' => $workDate.' 17:30:00',
        ]);

        // 休憩時間も更新されていることを確認
        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'break_start' => $workDate.' 12:00:00',
            'break_end' => $workDate.' 12:30:00',
        ]);
    }
}
