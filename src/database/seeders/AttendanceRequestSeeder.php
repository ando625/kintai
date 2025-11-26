<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\BreakTime;
use App\Models\BreakTimeRequest;
use Carbon\Carbon;
use App\Models\User;

class AttendanceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //対象ユーザー
        $user = User::where('email', 'rena.n@coachtech.com')->first();
        if (!$user) return;

        //既存勤怠を取得 11月
        $year = 2025;
        $month = 11;

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->orderBy('work_date')
            ->take(6)
            ->get();

        foreach ($attendances as $attendance) {
            //出勤退勤修正申請を作成
            $attendanceRequest = AttendanceRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'before_clock_in' => $attendance->clock_in,
                'before_clock_out' => $attendance->clock_out,
                // 微調整して「修正希望」に
                'after_clock_in' => $attendance->clock_in->copy()->addMinutes(rand(-20, 20)),
                'after_clock_out' => $attendance->clock_out->copy()->addMinutes(rand(-20, 20)),
                'before_remarks' => $attendance->remarks ?? '',
                'after_remarks' => '電車遅延のため',
                'status' => 'pending',
            ]);

            //休憩の修正申請を作成
            foreach ($attendance->breakTimes as $break) {
                BreakTimeRequest::create([
                    'attendance_request_id' => $attendanceRequest->id,
                    'break_time_id' => $break->id,
                    'before_start' => $break->break_start,
                    'before_end' => $break->break_end,
                    'after_start' => $break->break_start->copy()->addMinutes(rand(-20,20)),
                    'after_end' => $break->break_end->copy()->addMinutes(rand(-20,20)),
                ]);
            }
        }


    }
}
