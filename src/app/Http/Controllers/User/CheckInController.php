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

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        $status = $attendance ? $attendance->status : 'off_duty';

        return view('user.check-in', [
            'status' => $status,
            'serverTime' => $now->format('Y-m-d H:i'),
        ]);
    }


    public function clockIn()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => $today],
            ['clock_in' => $now]
        );

        $attendance->update(['status' => 'working']);

        return redirect()->route('user.check-in');

    }

    public function clockOut()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance && !$attendance->clock_out) {
            $attendance->update([
                'clock_out' => $now,
                'status' => 'finished'
            ]);
        }

        return redirect()->route('user.check-in');
    }


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


    public function breakEnd()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now()->format('H:i');

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if ($attendance) {
            $break = $attendance->breakTimes()->whereNull('break_end')->latest()->first();
            if ($break) {
                $break->update(['break_end' => $now]);
                $attendance->update(['status' => 'working']);
            }
        }

        return redirect()->route('user.check-in');
    }
}
