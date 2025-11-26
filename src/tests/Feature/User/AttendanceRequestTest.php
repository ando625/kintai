<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\BreakTimeRequest;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class AttendanceRequestTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    protected function createUserAndLogin()
    {
        // メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        $user = User::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        // 勤怠作成
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
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        return [$user, $attendance];
    }

    public function test_出勤時間が退勤時間より後の場合のバリデーション()
    {
        [$user, $attendance] = $this->createUserAndLogin();

        $response = $this->from("/attendance/{$attendance->id}/request")
            ->post("/attendance/{$attendance->id}/request", [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'break_times' => [['start' => '12:00', 'end' => '13:00']],
                'remarks' => 'テスト',
            ]);

        // バリデーション失敗で元ページに戻る
        $response->assertRedirect("/attendance/{$attendance->id}/request");

        // セッションにエラーがあることを確認
        $response->assertSessionHasErrors([
            'clock_in',
            'clock_out',
        ]);

        $errors = session('errors')->getMessages();
        $this->assertEquals(
            '出勤時間もしくは退勤時間が不適切な値です',
            $errors['clock_in'][0]
        );
        $this->assertEquals(
            '出勤時間もしくは退勤時間が不適切な値です',
            $errors['clock_out'][0]
        );
    }

    public function test_休憩開始時間が退勤時間より後の場合のバリデーション()
    {
        [$user, $attendance] = $this->createUserAndLogin();

        $response = $this->from("/attendance/{$attendance->id}/request")
            ->post("/attendance/{$attendance->id}/request", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_times' => [['start' => '19:00', 'end' => '20:00']],
                'remarks' => 'テスト',
            ]);

        // バリデーション失敗で元ページに戻る
        $response->assertRedirect("/attendance/{$attendance->id}/request");

        //セッションにエラー確認
        $response->assertSessionHasErrors(['break_times.0']);
        $errors = session('errors')->getMessages();
        $this->assertEquals('休憩時間が不適切な値です', $errors['break_times.0'][0]);
    }

    public function test_休憩終了時間が退勤時間より後の場合、バリデーション()
    {
        [$user, $attendance] = $this->createUserAndLogin();

        $response = $this->from("/attendance/{$attendance->id}/request")
            ->post("/attendance/{$attendance->id}/request", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_times' => [['start' => '12:00', 'end' => '19:00']],
                'remarks' => 'テスト',
            ]);

        // バリデーション失敗で元ページに戻る
        $response->assertRedirect("/attendance/{$attendance->id}/request");

        //セッションにエラー確認
        $response->assertSessionHasErrors(['break_times.0']);
        $errors = session('errors')->getMessages();
        $this->assertEquals('休憩時間もしくは退勤時間が不適切な値です', $errors['break_times.0'][0]);

    }

    public function test_備考欄が未入力の場合のバリデーション()
    {
        [$user, $attendance] = $this->createUserAndLogin();

        $response = $this->from("/attendance/{$attendance->id}/request")
            ->post("/attendance/{$attendance->id}/request", [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_times' => [['start' => '12:00', 'end' => '19:00']],
                'remarks' => '',
            ]);

        // バリデーション失敗で元ページに戻る
        $response->assertRedirect("/attendance/{$attendance->id}/request");

        $response->assertSessionHasErrors(['remarks']);

        $errors = session('errors')->getMessages();
        $this->assertEquals('備考を記入してください', $errors['remarks'][0]);
    }


    public function test_修正申請が正しく作成される()
    {
        // メール認証スキップ
        $this->withoutMiddleware(\Illuminate\Auth\Middleware\EnsureEmailIsVerified::class);

        // ユーザー作成＆ログイン
        $user = User::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($user, 'web');

        // 固定の勤怠を作成
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => Carbon::parse('2025-01-10'),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);

        // 休憩作成
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        // 修正申請を送信
        $response = $this->post("/attendance/{$attendance->id}/request", [
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'break_times' => [['start' => '11:00', 'end' => '12:00']],
            'remarks' => 'テスト修正申請',
        ]);

        // リダイレクトとセッション確認
        $response->assertRedirect(route('user.show', $attendance->id));
        $response->assertSessionHas('success', '勤怠の修正申請を送信しました');

        // DB から修正申請を取得
        $attendanceRequest = AttendanceRequest::where('attendance_id', $attendance->id)->first();

        // AttendanceRequest の内容確認
        $this->assertEquals('08:00', Carbon::parse($attendanceRequest->after_clock_in)->format('H:i'));
        $this->assertEquals('17:00', Carbon::parse($attendanceRequest->after_clock_out)->format('H:i'));
        $this->assertEquals('テスト修正申請', $attendanceRequest->after_remarks);
        $this->assertEquals('pending', $attendanceRequest->status);

        // 休憩申請の確認
        $breakTimeRequest = BreakTimeRequest::where('attendance_request_id', $attendanceRequest->id)->first();
        $this->assertEquals('11:00', Carbon::parse($breakTimeRequest->after_start)->format('H:i'));
        $this->assertEquals('12:00', Carbon::parse($breakTimeRequest->after_end)->format('H:i'));
    }

    public function test_申請一覧画面で承認待ちと承認済みが正しく表示される()
    {
        // ユーザーと勤怠作成
        [$user, $attendance] = $this->createUserAndLogin();

        // ユーザーが修正申請を出す（pending）
        $request = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'after_clock_in' => '08:00:00',
            'after_clock_out' => '17:00:00',
            'after_remarks' => '遅刻対応',
            'status' => 'pending',
        ]);
        BreakTimeRequest::create([
            'attendance_request_id' => $request->id,
            'after_start' => '11:00:00',
            'after_end' => '12:00:00',
        ]);

        // 承認待ちタブ確認
        $response = $this->get('/my_requests?tab=pending');
        $response->assertStatus(200);
        $response->assertSee('遅刻対応');


        // 管理者が承認
        $admin = Admin::create([
            'email' => 'admin@example.com',
            'password' => Hash::make('pass1234'),
        ]);
        $this->actingAs($admin, 'admin');

        $request->update(['status' => 'approved']);


        // ユーザー目線で承認済みタブ確認
        $this->actingAs($user);
        $response = $this->get('/my_requests?tab=approved');
        $response->assertStatus(200);
        $response->assertSee('遅刻対応');

        // 承認待ちタブにはもう表示されない
        $response = $this->get('/my_requests?tab=pending');
        $response->assertStatus(200);
        $response->assertDontSee('遅刻対応');

    }

    public function test_申請一覧詳細リングで勤怠詳細画面に飛べる()
    {
        [$user] = $this->createUserAndLogin();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2025-01-12',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'finished',
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start' => '12:00',
            'break_end' => '13:00',
        ]);

        $request = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'after_clock_in' => '08:00:00',
            'after_clock_out' => '17:00:00',
            'after_remarks' => '修正メモ',
            'status' => 'pending',
        ]);
        BreakTimeRequest::create([
            'attendance_request_id' => $request->id,
            'after_start' => '11:00:00',
            'after_end'   => '12:00:00',
        ]);

        $response = $this->get('/my_requests?tab=pending');
        $response = $this->get("/attendance/detail/{$attendance->id}");
        $response->assertStatus(200);
        $response->assertSee($attendance->work_date->format('2025年'));
        $response->assertSee($attendance->work_date->format('1月12日'));

        $response->assertSee('08:00');
        $response->assertSee('17:00');
        $response->assertSee('11:00');
        $response->assertSee('12:00');
        $response->assertSee('修正メモ');
    }

}
