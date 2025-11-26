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

class AttendanceShowTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_勤怠詳細画面に表示されるデータが選択したものになっている()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $date = '2025-11-20';
        $user1 = User::create([
            'name' => 'テスト1',
            'email' => 'test@example.com',
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

        $response = $this->get("/admin/attendance/{$attendance1->id}");
        $response->assertStatus(200);

        $response->assertSee('テスト1');
        $response->assertSee('2025年');
        $response->assertSee('11月20日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('0:15');

    }

    public function test_出勤時間が退勤時間より後の場合の管理者側バリデーション()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $date = '2025-11-20';
        $user1 = User::create([
            'name' => 'テスト1',
            'email' => 'test@example.com',
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


        $response = $this->from("/admin/attendance/{$attendance1->id}")
            ->post("/admin/attendance/{$attendance1->id}", [
                'clock_in' => '20:00', // 出勤が退勤より後
                'clock_out' => '19:00',
            ]);

            $response->assertRedirect("/admin/attendance/{$attendance1->id}");
            $response->assertSessionHasErrors(['clock_in', 'clock_out']);
            $errors = session('errors')->getMessages();
            $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors['clock_in'][0]);
            $this->assertEquals('出勤時間もしくは退勤時間が不適切な値です', $errors['clock_out'][0]);
    }

    public function test_休憩開始時間が退勤時間より後の場合のエラー表示()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $date = '2025-11-20';
        $user1 = User::create([
            'name' => 'テスト1',
            'email' => 'test@example.com',
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

        $response = $this->from("/admin/attendance/{$attendance1->id}")
            ->post("/admin/attendance/{$attendance1->id}", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_times' => [
                    ['start' => '19:00', 'end' => '19:30'],
                ],
                'remarks' => 'テスト',
            ]);

        $response->assertRedirect("/admin/attendance/{$attendance1->id}");
        $response->assertSessionHasErrors(['break_times.0']);

        $this->assertEquals(
            '休憩時間が不適切な値です',
            session('errors')->first('break_times.0')
        );

    }

    public function test_休憩終了時間が退勤時間より後の場合のエラー表示()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $date = '2025-11-20';
        $user1 = User::create([
            'name' => 'テスト1',
            'email' => 'test@example.com',
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

        $response = $this->from("/admin/attendance/{$attendance1->id}")
            ->post("/admin/attendance/{$attendance1->id}", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
            'break_times' => [
                ['start' => '17:00', 'end' => '19:00'],
            ],
                'remarks' => 'テスト',
            ]);

        $response->assertRedirect("/admin/attendance/{$attendance1->id}");
        $response->assertSessionHasErrors(['break_times.0']);

        $this->assertEquals(
            '休憩時間もしくは退勤時間が不適切な値です',
            session('errors')->first('break_times.0')
        );
    }

    public function test_備考欄が未入力の場合のエラー表示()
    {
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $date = '2025-11-20';
        $user1 = User::create([
            'name' => 'テスト1',
            'email' => 'test@example.com',
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

        $response = $this->from("/admin/attendance/{$attendance1->id}")
            ->post("/admin/attendance/{$attendance1->id}", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_start' => '17:00',
                'break_end' => '19:00',
                'remarks' => '',
            ]);

        $response->assertRedirect("/admin/attendance/{$attendance1->id}");
        $response->assertSessionHasErrors(['remarks']);

        $this->assertEquals('備考を記入してください', session('errors')->first('remarks'));
    }
}
