<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;


class CheckInController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        //今日の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        //ステータス判定
        $status = $attendance ? $attendance->status : 'off_duty';
        //デフォルト：勤務外

        // Blade にサーバー時刻を渡す
        return view('user.check-in', [
            'status' => $status,
            'serverTime' => $now->format('Y-m-d H:i'),
        ]);
    }

    //出勤処理
    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['clock_in' => $now]
        );

        //出勤したのでステータスを更新
        $attendance->update(['status' => 'working']);

        return redirect()->route('user.check-in');

    }

    //退勤処理
    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        //もし今日の勤怠レコードが存在していて、かつまだ退勤時刻が記録されていなければ
        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out' => $now,
                'status' => 'finished'
            ]);
        }

        return redirect()->route('user.check-in');
    }

    //休憩開始処理
    public function breakStart()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            $attendance->breakTimes()->create([
                'break_start' => $now,]);
            $attendance->update(['status' => 'break']);
        }

        return redirect()->route('user.check-in');
    }

    //休憩終了処理
    public function breakEnd()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            // 直近の break_end が null のレコードを更新
            $break = $attendance->breakTimes()->whereNull('break_end')->latest()->first();
            if ($break) {
                $break->update(['break_end' => $now]);
                $attendance->update(['status' => 'working']);
            }
        }

        return redirect()->route('user.check-in');
    }
}
