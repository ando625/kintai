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

class AttendanceShowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    protected function createUserAndLoginWithAttendance()
    {
        // メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // ユーザー作成
        $user = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);

        // ログイン
        $this->actingAs($user, 'web');

        // 勤怠作成（固定日付）
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-01-10',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);

        // 休憩作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '2025-01-10 12:00:00',
            'break_end' => '2025-01-10 13:00:00',
        ]);

        // 必要に応じて attendance も返す
        return [
            'user' => $user,
            'attendance' => $attendance,
        ];
    }

    public function test_勤怠詳細画面に正しい情報が表示される()
    {
        $loginDate = $this->createUserAndLoginWithAttendance();
        $user = $loginDate['user'];
        $attendance = $loginDate['attendance'];

        //勤怠ページにアクセス
        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);

        //名前がログインユーザー
        $response->assertSee($user->name);

        //日付が勤怠のwork_date
        $response->assertSee('2025年');
        $response->assertSee('1月10日');

        //出勤・退勤時間
        $response->assertSee('09:00');
        $response->assertSee('18:00');

        //休憩時間
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
}
