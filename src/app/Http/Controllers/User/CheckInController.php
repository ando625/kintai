<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
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
        $status = 'off_duty';  //デフォルト：勤務外

        if ($attendance) {
            $latestBreak = $attendance->breakTimes()->latest()->first();
            if ($latestBreak && !$latestBreak->break_end) {
                $status = 'break';
            } elseif ($attendance->clock_in && !$attendance->clock_out) {
                $status = 'working';
            } elseif ($attendance->clock_out) {
                $status = 'finished';
            }
        }

        // Blade にサーバー時刻を渡す
        return view('user.check-in', [
            'status' => $status,
            'serverTime' => $now->format('Y-m-d H:i:s'),
        ]);
    }

    //出勤処理
    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['clock_in' => $now]
        );

        return redirect()->route('user.check-in');

    }

    //退勤処理
    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        //もし今日の勤怠レコードが存在していて、かつまだ退勤時刻が記録されていなければ
        if ($attendance && !$attendance->clock_out) {
            $attendance->update(['clock_out' => $now]);
        }


        return redirect()->route('user.check-in');
    }

    //休憩開始処理
    public function breakStart()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            $attendance->breakTimes()->create([
                'break_start' => $now,
            ]);
        }

        return redirect()->route('user.check-in');
    }

    //休憩終了処理
    public function breakEnd()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i:s');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            // 直近の break_end が null のレコードを更新
            $break = $attendance->breakTimes()->whereNull('break_end')->latest()->first();
            if ($break) {
                $break->update(['break_end' => $now]);
            }
        }

        return redirect()->route('user.check-in');
    }
}
