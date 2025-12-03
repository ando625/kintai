<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Http\Controllers\Controller;
use App\Http\Requests\AmendmentRequest;
use Carbon\Carbon;
use App\Models\User;
use App\Models\AttendanceRequest;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $targetDate = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();
        $today = Carbon::today();

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $targetDate)
            ->get();

        $prevDate = $targetDate->copy()->subDay()->toDateString();
        $nextDate = $targetDate->copy()->addDay()->toDateString();

        return view('admin.index', compact('attendances', 'targetDate', 'today', 'prevDate', 'nextDate'));
    }


    public function staffAttendance($id, Request $request)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth   = $currentMonth->copy()->endOfMonth();

        $attendances = Attendance::with('breakTimes')
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
                $daysInMonth[] = $attendances->get($workDate);
            } else {
                $daysInMonth[] = new Attendance([
                    'id' => null,
                    'work_date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                    'remarks' => null,
                    'breakTimes' => collect([]),
                ]);
            }
            $date->addDay();
        }

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        return view('admin.staff.show', compact(
            'user',
            'daysInMonth',
            'currentMonth',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function show($id)
    {
        $attendance = Attendance::with(['user', 'breakTimes', 'latestRequest.breakTimeRequests'])->findOrFail($id);
        $user = $attendance->user ?? new User(['name' => '未登録']);

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

        $display = [
            'clock_in'  => $clockIn ? Carbon::parse($clockIn)->format('H:i') : '',
            'clock_out' => $clockOut ? Carbon::parse($clockOut)->format('H:i') : '',
            'remarks'   => $useRequest?->after_remarks ?? $attendance->remarks,
            'breaks'    => $displayBreaks,
        ];

        return view('admin.show', compact('attendance', 'user', 'display', 'isPending'));
    }

    public function update(AmendmentRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        if ($attendance->latestRequest?->status === 'pending') {
            return redirect()->back();
        }

        $attendance->update([
            'clock_in'  => $request->clock_in,
            'clock_out' => $request->clock_out,
            'remarks'   => $request->remarks,
        ]);

        $breakTimesInput = $request->break_times ?? [];

        foreach ($breakTimesInput as $index => $input) {
            $start = $input['start'] ?? null;
            $end = $input['end'] ?? null;

            $existing = $attendance->breakTimes[$index] ?? null;

            if ($start && $end) {
                if ($existing) {
                    $existing->update([
                        'break_start' => $start,
                        'break_end' => $end
                    ]);
                } else {
                    $attendance->breakTimes()->create([
                        'break_start' => $start,
                        'break_end' => $end
                    ]);
                }
            } else {
                $existing?->delete();
            }
        }
        return redirect()->route('admin.attendance.show', $attendance->id)
            ->with('success', '勤怠情報を更新しました');
    }


    public function staffList()
    {
        $users = User::all();

        return view('admin.staff.list', compact('users'));
    }


    public function requests()
    {
        $pendingRequests = AttendanceRequest::with('attendance', 'user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        $approvedRequests = AttendanceRequest::with('attendance', 'user')
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('admin.requests', compact('pendingRequests','approvedRequests'));
    }

    public function showRequest($id)
    {
        $attendanceRequest = AttendanceRequest::with(['attendance.user','breakTimeRequests'])->findOrFail($id);

        $user = $attendanceRequest->attendance->user;


        return view('admin.request-approve', compact('attendanceRequest', 'user'));

    }

    public function approveRequest(Request $request, $id)
    {
        $attendanceRequest = AttendanceRequest::with(['breakTimeRequests', 'user'])->findOrFail($id);
        $user = $attendanceRequest->user;

        $attendance = Attendance::findOrFail($attendanceRequest->attendance_id);

        $attendance->update([
            'clock_in'  => $attendanceRequest->after_clock_in,
            'clock_out' => $attendanceRequest->after_clock_out,
            'remarks'   => $attendanceRequest->after_remarks,
        ]);

        foreach ($attendanceRequest->breakTimeRequests as $btr) {
            $breakTimeId = $btr->break_time_id;

            $existing = $breakTimeId
                ? $attendance->breakTimes()->find($breakTimeId)
                : null;

            $start = $btr->after_start;
            $end = $btr->after_end;

            if (empty($start) && empty($end)) {
                $existing?->delete();
                continue;
            }

            if ($existing) {
                $existing->update([
                    'break_start' => $start,
                    'break_end' => $end,
                ]);
                continue;
            }

            $attendance->breakTimes()->create([
                'break_start' => $start,
                'break_end' => $end,
            ]);

        }

        $attendanceRequest->update(['status' => 'approved']);

        return redirect()
            ->route('admin.requests.approve.show', $attendanceRequest->id);
    }


    public function exportCsv(Request $request, $id)
    {
        $month = $request->query('month');

        $user = User::findOrFail($id);

        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', substr($month, 0, 4))
            ->whereMonth('work_date', substr($month, 5, 2))
            ->get();

        $fileName = "attendance_{$user->id}_{$month}.csv";
        $handle = fopen('php://temp', 'r+');

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['ユーザー名', $user->name]);

        $csvHeader = [
            '日付','出勤','退勤','休憩','合計',
        ];
        fputcsv($handle, $csvHeader);

        $csvData = [];
        foreach ($attendances as $attendance) {

            fputcsv($handle, [
                $attendance->work_date->format('Y-m-d'),
                $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
                $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
                $attendance->break_hours_formatted,
                $attendance->work_hours_formatted,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header("Content-Disposition", "attachment; filename={$fileName}");
    }



}
