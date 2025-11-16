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

        // 表示する月を決定
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth   = $currentMonth->copy()->endOfMonth();

        // 勤怠データを取得し、日付でキー化
        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startOfMonth, $endOfMonth])
            ->orderBy('work_date', 'asc')
            ->get()
            ->keyBy(function ($item) {
                return $item->work_date->format('Y-m-d');
            });

        // 月の日付を1日ずつループして空白データも生成
        $daysInMonth = [];
        $date = $startOfMonth->copy();

        while ($date->lte($endOfMonth)) {
            $workDate = $date->format('Y-m-d');
            $daysInMonth[] = $attendances->get($workDate) ?? new Attendance([
                'work_date' => $date,
                'clock_in' => null,
                'clock_out' => null,
                'remarks' => null,
            ]);
            $date->addDay();
        }

        // 前月・翌月リンク
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('user.index', compact(
            'user',
            'daysInMonth',
            'currentMonth',
            'prevMonth',
            'nextMonth'
        ));

    }

    public function show($id)
    {
        $user = Auth::user();

        $attendance = Attendance::with(['breakTimes', 'latestRequest.breakTimeRequests'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $display = [
            'clock_in'  => $attendance->latestRequest?->after_clock_in
                ? \Carbon\Carbon::parse($attendance->latestRequest->after_clock_in)->format('H:i')
                : ($attendance->clock_in ? $attendance->clock_in->format('H:i') : ''),
            'clock_out' => $attendance->latestRequest?->after_clock_out
                ? \Carbon\Carbon::parse($attendance->latestRequest->after_clock_out)->format('H:i')
                : ($attendance->clock_out ? $attendance->clock_out->format('H:i') : ''),
            'remarks'   => $attendance->latestRequest?->after_remarks ?? $attendance->remarks,
            'breaks'    => [],
        ];

        $breakTimes = $attendance->breakTimes;
        $isPending = $attendance->latestRequest?->status === 'pending';

        // 最新申請がある場合、breakTimeRequests を優先
        if ($attendance->latestRequest?->breakTimeRequests->count() > 0) {
            foreach ($attendance->latestRequest->breakTimeRequests as $i => $breakRequest) {
                $display['breaks'][] = [
                    'start' => $breakRequest->after_start
                        ? \Carbon\Carbon::parse($breakRequest->after_start)->format('H:i')
                        : ($breakTimes[$i]->break_start ? \Carbon\Carbon::parse($breakTimes[$i]->break_start)->format('H:i') : ''),
                    'end'   => $breakRequest->after_end
                        ? \Carbon\Carbon::parse($breakRequest->after_end)->format('H:i')
                        : ($breakTimes[$i]->break_end ? \Carbon\Carbon::parse($breakTimes[$i]->break_end)->format('H:i') : ''),
                ];
            }
        } else {
            // 最新申請がない場合は attendance 側の breakTimes をそのまま
            foreach ($breakTimes as $break) {
                $display['breaks'][] = [
                    'start' => $break->break_start ? \Carbon\Carbon::parse($break->break_start)->format('H:i') : '',
                    'end'   => $break->break_end ? \Carbon\Carbon::parse($break->break_end)->format('H:i') : '',
                ];
            }
        }

        // 編集可能モードなら最低2行保証
        if (!$isPending && count($display['breaks']) < 2) {
            while (count($display['breaks']) < 2) {
                $display['breaks'][] = ['start' => '', 'end' => ''];
            }
        }

        return view('user.show', compact('attendance', 'user', 'display'));
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
