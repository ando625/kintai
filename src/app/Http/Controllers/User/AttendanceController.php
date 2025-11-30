<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AmendmentRequest;
use App\Models\BreakTimeRequest;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $currentMonth = $request->query('month') ? Carbon::parse($request->query('month')) : Carbon::now();
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth   = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with(['breakTimes', 'latestRequest.breakTimeRequests'])
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(fn($item) => $item->work_date->format('Y-m-d'));

        $daysInMonth = [];
        $date = $startOfMonth->copy();

        while ($date->lte($endOfMonth)) {
            $workDate = $date->format('Y-m-d');

            if ($attendances->has($workDate)) {
                $att = $attendances->get($workDate);

                $daysInMonth[] = $att;

            } else {
                $daysInMonth[] = new Attendance([
                    'id' => null,
                    'work_date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                    'remarks' => null,
                    'breakTimes' => collect([]),
                    'break_hours_formatted' => '-',
                    'work_hours_formatted' => '',
                ]);
            }

            $date->addDay();
        }

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('user.index', compact('user', 'daysInMonth', 'currentMonth', 'prevMonth', 'nextMonth'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $attendance = Attendance::with(['breakTimes', 'latestRequest.breakTimeRequests'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        //修正申請中か判定
        $isPending = $attendance->latestRequest?->status === 'pending';
        $useRequest = $isPending ? $attendance->latestRequest : null;

        //表示値の設定　修正申請中ならafter使う　ないならDBの値を使う
        $clockIn  = $useRequest?->after_clock_in ?? $attendance->clock_in;
        $clockOut = $useRequest?->after_clock_out ?? $attendance->clock_out;
        $remarks = $useRequest?->after_remarks ?? $attendance->remarks;
        $breakTimes = $useRequest?->breakTimeRequests ?? $attendance->breakTimes;

        //休憩表示用配列作成
        $displayBreaks = [];
        foreach ($breakTimes as $b) {
            $start = $useRequest ? $b->after_start : $b->break_start;
            $end   = $useRequest ? $b->after_end   : $b->break_end;
            $displayBreaks[] = [
                'start' => $start ? Carbon::parse($start)->format('H:i') : '',
                'end'   => $end ? Carbon::parse($end)->format('H:i') : '',
            ];
        }


        $display = [
            'clock_in' => $clockIn ? Carbon::parse($clockIn)->format('H:i') : '',
            'clock_out' => $clockOut ? Carbon::parse($clockOut)->format('H:i') : '',
            'remarks' => $useRequest?->after_remarks ?? $attendance->remarks,
            'breaks' => $displayBreaks,
        ];

        return view('user.show', compact('attendance', 'user', 'display', 'isPending'));
    }



    public function storeRequest(AmendmentRequest $request, $id)
    {
        $user = Auth::user();
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // AttendanceRequest 作成
        $attendanceRequest = AttendanceRequest::create([
            'attendance_id'   => $attendance->id,
            'user_id'         => $user->id,
            'before_clock_in' => $attendance->clock_in?->format('H:i'),
            'before_clock_out' => $attendance->clock_out?->format('H:i'),
            'after_clock_in'  => $request->clock_in,
            'after_clock_out' => $request->clock_out,
            'before_remarks'  => $attendance->remarks,
            'after_remarks'   => $request->remarks,
            'status'          => 'pending',
        ]);

        // 休憩申請
        $breakTimesInput = $request->break_times ?? [];

        foreach ([0, 1] as $i) {
            $input = $breakTimesInput[$i] ?? ['start' => null, 'end' => null];
            $start = $input['start'];
            $end   = $input['end'];
            $attendanceBreak = $attendance->breakTimes[$i] ?? null;

            // 日付と結合して DATETIME に変換
            $workDate = $attendance->work_date->format('Y-m-d');
            $startDatetime = $start ? Carbon::parse("$workDate $start") : null;
            $endDatetime   = $end   ? Carbon::parse("$workDate $end")   : null;

            BreakTimeRequest::create([
                'attendance_request_id' => $attendanceRequest->id,
                'break_time_id'         => $attendanceBreak?->id,
                'before_start'          => $attendanceBreak?->break_start,
                'before_end'            => $attendanceBreak?->break_end,
                'after_start'           => $startDatetime,
                'after_end'             => $endDatetime,
            ]);
        }

        return redirect()->route('user.show', $attendance->id)
            ->with('success', '勤怠の修正申請を送信しました');
    }

    public function requests()
    {
        $user = Auth::user();

        // 承認待ち
        $pendingRequests = AttendanceRequest::with('attendance', 'user')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        // 承認済み
        $approvedRequests = AttendanceRequest::with('attendance', 'user')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc')
            ->get();

        return view(
            'user.requests',
            compact('pendingRequests', 'approvedRequests'));
    }
}
