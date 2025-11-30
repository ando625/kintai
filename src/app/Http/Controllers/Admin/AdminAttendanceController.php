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
        // URLパラメータ（日付指定）があればそれを使う。なければ今日。
        $targetDate = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::today();

        // 今日の日付
        $today = Carbon::today();

        // 勤怠データ取得
        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $targetDate)
            ->get();

        // 前後の日付リンク
        $prevDate = $targetDate->copy()->subDay()->toDateString();
        $nextDate = $targetDate->copy()->addDay()->toDateString();

        return view('admin.index', compact('attendances', 'targetDate', 'today', 'prevDate', 'nextDate'));
    }

    //ユーザー単位での月表示
    public function staffAttendance($id, Request $request)
    {
        $user = User::findOrFail($id);

        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth   = $currentMonth->copy()->endOfMonth();

        //指定つきの勤怠を取得（修正申請は無視、DBの最新値だけ）
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

        //承認待ちは更新不可
        if ($attendance->latestRequest?->status === 'pending') {
            return redirect()->back();
        }

        // 勤怠本体を更新
        $attendance->update([
            'clock_in'  => $request->clock_in,
            'clock_out' => $request->clock_out,
            'remarks'   => $request->remarks,
        ]);

        // 休憩データ更新
        $breakTimesInput = $request->break_times ?? [];

        foreach ($breakTimesInput as $index => $input) {
            $start = $input['start'] ?? null;
            $end = $input['end'] ?? null;

            //既存の休憩レコードがあれば取得
            $existing = $attendance->breakTimes[$index] ?? null;

            if ($start && $end) {
                //両方入力されていれば作成or更新
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
                //入力が空なら既存の休憩は削除
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

        //承認待ち
        $pendingRequests = AttendanceRequest::with('attendance', 'user')
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        //承認済み
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
        // AttendanceRequest を取得（休憩とユーザーも取得）
        $attendanceRequest = AttendanceRequest::with(['breakTimeRequests', 'user'])->findOrFail($id);
        $user = $attendanceRequest->user;

        // 対応する Attendance を取得（必ず存在する前提）
        $attendance = Attendance::findOrFail($attendanceRequest->attendance_id);

        // 勤怠本体を更新
        $attendance->update([
            'clock_in'  => $attendanceRequest->after_clock_in,
            'clock_out' => $attendanceRequest->after_clock_out,
            'remarks'   => $attendanceRequest->after_remarks,
        ]);

        // 休憩の更新
        foreach ($attendanceRequest->breakTimeRequests as $btr) {
            $breakTimeId = $btr->break_time_id;

            //既存の休憩を取得
            $existing = $breakTimeId
                ? $attendance->breakTimes()->find($breakTimeId)
                : null;

            $start = $btr->after_start;
            $end = $btr->after_end;

            //入力が空->削除
            if (empty($start) && empty($end)) {
                $existing?->delete();
                continue;
            }

            //入力あり・既存あり-> 更新
            if ($existing) {
                $existing->update([
                    'break_start' => $start,
                    'break_end' => $end,
                ]);
                continue;
            }

            //入力あり・既存なし->新規作成
            $attendance->breakTimes()->create([
                'break_start' => $start,
                'break_end' => $end,
            ]);

        }

        // 申請を承認済みに
        $attendanceRequest->update(['status' => 'approved']);

        return redirect()
            ->route('admin.requests.approve.show', $attendanceRequest->id);
    }

    //CSV出力
    public function exportCsv(Request $request, $id)
    {
        //月の取得
        $month = $request->query('month');

        //対象ユーザー取得
        $user = User::findOrFail($id);

        //指定月の勤怠取得
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', substr($month, 0, 4))
            ->whereMonth('work_date', substr($month, 5, 2))
            ->get();

        //CSVに変換
        $fileName = "attendance_{$user->id}_{$month}.csv";
        $handle = fopen('php://temp', 'r+');
        //文字化け対策
        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, ['ユーザー名', $user->name]);

        //CSVヘッダー
        $csvHeader = [
            '日付','出勤','退勤','休憩','合計',
        ];
        //ヘッダー
        fputcsv($handle, $csvHeader);

        $csvData = [];
        foreach ($attendances as $attendance) {

            //CSV本体作成
            fputcsv($handle, [
                $attendance->work_date->format('Y-m-d'),
                $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
                $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
                $attendance->break_hours_formatted,
                $attendance->work_hours_formatted,
            ]);
        }

        rewind($handle);  //書き込んだデータを先頭に戻す
        $csv = stream_get_contents($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header("Content-Disposition", "attachment; filename={$fileName}");
    }



}
