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
                $useRequest = $att->latestRequest?->status === 'pending' ? $att->latestRequest : null;

                $breaks = ($useRequest?->breakTimeRequests->count() > 0 ? $useRequest->breakTimeRequests : $att->breakTimes)
                    ->map(function ($b) use ($useRequest) {
                        $start = $useRequest ? $b->after_start : $b->break_start;
                        $end   = $useRequest ? $b->after_end   : $b->break_end;
                        return ($start && $end) ? Carbon::parse($start)->format('H:i') . '-' . Carbon::parse($end)->format('H:i') : null;
                    })->filter()->values()->all();

                $att->break_hours_formatted = $breaks ? implode(' / ', $breaks) : '-';

                $clockIn  = $useRequest?->after_clock_in ?? $att->clock_in;
                $clockOut = $useRequest?->after_clock_out ?? $att->clock_out;

                $workMinutes = 0;
                if ($clockIn && $clockOut) {
                    $diff = Carbon::parse($clockIn)->diffInMinutes(Carbon::parse($clockOut));
                    $breakMinutes = 0;
                    foreach ($useRequest?->breakTimeRequests ?? $att->breakTimes as $b) {
                        $start = $useRequest ? $b->after_start : $b->break_start;
                        $end   = $useRequest ? $b->after_end   : $b->break_end;
                        if ($start && $end) $breakMinutes += Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
                    }
                    $workMinutes = $diff - $breakMinutes;
                }

                $att->work_hours_formatted = sprintf('%02d:%02d', intdiv($workMinutes, 60), $workMinutes % 60);
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

        $isPending = $attendance->latestRequest?->status === 'pending';
        $useRequest = $isPending ? $attendance->latestRequest : null;

        $clockIn  = $useRequest?->after_clock_in ?? $attendance->clock_in;
        $clockOut = $useRequest?->after_clock_out ?? $attendance->clock_out;

        $breakTimes = $useRequest?->breakTimeRequests ?? $attendance->breakTimes;
        $displayBreaks = [];
        foreach ($breakTimes as $b) {
            $start = $useRequest ? $b->after_start : $b->break_start;
            $end   = $useRequest ? $b->after_end   : $b->break_end;
            $displayBreaks[] = [
                'start' => $start ? Carbon::parse($start)->format('H:i') : '',
                'end'   => $end ? Carbon::parse($end)->format('H:i') : '',
            ];
        }

        $workMinutes = 0;
        if ($clockIn && $clockOut) {
            $diff = Carbon::parse($clockIn)->diffInMinutes(Carbon::parse($clockOut));
            $breakMinutes = 0;
            foreach ($breakTimes as $b) {
                $start = $useRequest ? $b->after_start : $b->break_start;
                $end   = $useRequest ? $b->after_end   : $b->break_end;
                if ($start && $end) $breakMinutes += Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
            }
            $workMinutes = $diff - $breakMinutes;
        }

        $display = [
            'clock_in' => $clockIn ? Carbon::parse($clockIn)->format('H:i') : '',
            'clock_out' => $clockOut ? Carbon::parse($clockOut)->format('H:i') : '',
            'remarks' => $useRequest?->after_remarks ?? $attendance->remarks,
            'breaks' => $displayBreaks,
            'work_hours_formatted' => sprintf('%02d:%02d', intdiv($workMinutes, 60), $workMinutes % 60),
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

            // 両方空なら無視（申請には保存しない）
            if (empty($start) && empty($end)) continue;

            BreakTimeRequest::create([
                'attendance_request_id' => $attendanceRequest->id,
                'break_time_id'         => $attendanceBreak?->id,
                'before_start'          => $attendanceBreak?->break_start?->format('H:i') ?? null,
                'before_end'            => $attendanceBreak?->break_end?->format('H:i') ?? null,
                'after_start'           => $start,
                'after_end'             => $end,
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
            ->distinct('id')
            ->get();

        // 承認済み
        $approvedRequests = AttendanceRequest::with('attendance', 'user')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc')
            ->distinct('id')
            ->get();

        return view(
            'user.requests',
            compact('pendingRequests', 'approvedRequests'));
    }
}
