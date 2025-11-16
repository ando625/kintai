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

        // 未来の日付指定があったら今日に戻す
        if ($targetDate->gt($today)) {
            $targetDate = $today;
        }

        // 勤怠データ取得
        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $targetDate)
            ->get();

        // 前後の日付リンク
        $prevDate = $targetDate->copy()->subDay()->toDateString();
        $nextDate = $targetDate->copy()->addDay()->toDateString();

        if ($nextDate > $today->toDateString()) {
            $nextDate = null; // 翌日が未来なら非表示にする
        }

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

        // Attendance 取得（breakTimes と一緒に）
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
                $att = $attendances->get($workDate);

                // BreakTime を HH:MM 形式で結合
                $breaks = $att->breakTimes->map(function ($b) {
                    if ($b->break_start && $b->break_end) {
                        return Carbon::parse($b->break_start)->format('H:i') . '-' . Carbon::parse($b->break_end)->format('H:i');
                    }
                    return null;
                })->filter()->values()->all();

                // 休憩をまとめて文字列化
                $att->break_hours_formatted = $breaks ? implode(' / ', $breaks) : '-';

                // 実働時間（HH:MM）も更新
                $att->work_hours_formatted = $att->work_hours_formatted; // アクセサで計算済み

                $daysInMonth[] = $att;
            } else {
                // DBにない日 → 空オブジェクト
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

        //申請中か判定
        $isPending = $attendance->latestRequest?->status === 'pending';

        $display = [
            'clock_in' => $attendance->latestRequest?->after_clock_in
                ? Carbon::parse($attendance->latestRequest->after_clock_in)->format('H:i')
                : ($attendance->clock_in ? $attendance->clock_in->format('H:i') : ''),

            'clock_out' => $attendance->latestRequest?->after_clock_out
                ? Carbon::parse($attendance->latestRequest->after_clock_out)->format('H:i')
                : ($attendance->clock_out ? $attendance->clock_out->format('H:i') : ''),

            'remarks' => $attendance->latestRequest?->after_remarks ?? $attendance->remarks,

            'breaks' => [],
        ];

        //休憩データを配列に詰める
        $breakTimes = $attendance->breakTimes->sortBy('id')->values();

        foreach ($breakTimes as $i => $break) {

            //修正申請のbreakTimeRequest
            $breakRequest = $attendance->latestRequest?->breakTimeRequests->get($i);

            $start = $breakRequest?->after_start ?? $break->break_start;
            $end   = $breakRequest?->after_end   ?? $break->break_end;

            //修正申請中で両方空ならスキップ
            if ($isPending && empty($start) && empty($end)) {
                continue;
            }

            $display['breaks'][] = [
                'start' => $start ? Carbon::parse($start)->format('H:i') : '',
                'end'   => $end   ? Carbon::parse($end)->format('H:i') : '',
            ];
        }

        // ===== ここが最重要 =====
        // ループの「外」で必ず 2 件にそろえる
        while (count($display['breaks']) < 2) {
            $display['breaks'][] = ['start' => '', 'end' => ''];
        }

        return view('admin.show', compact('attendance', 'user', 'display', 'isPending'));
    }

    public function update(AmendmentRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // 勤怠本体を更新
        $attendance->update([
            'clock_in'  => $request->clock_in,
            'clock_out' => $request->clock_out,
            'remarks'   => $request->remarks,
        ]);

        // 休憩データ更新
        $breakTimesInput = $request->break_times ?? [];

        foreach ([0, 1] as $i) {
            $input = $breakTimesInput[$i] ?? ['start' => null, 'end' => null];
            $start = $input['start'];
            $end   = $input['end'];
            $existing = $attendance->breakTimes[$i] ?? null;

            // 両方空なら無視
            if (empty($start) && empty($end)) {
                if ($existing && $i === 1) {
                    $existing->delete(); // 休憩2は削除
                }
                continue;
            }

            // 片方だけ → エラーは FormRequest で処理済み
            // 両方入力 → 更新 or 新規作成
            if ($existing) {
                $existing->update(['break_start' => $start, 'break_end' => $end]);
            } else {
                $attendance->breakTimes()->create(['break_start' => $start, 'break_end' => $end]);
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
            $breakTime = $attendance->breakTimes()->where('id', $btr->break_time_id)->first();

            if (empty($btr->after_start) && empty($btr->after_end)) {
                // 両方空なら削除
                $breakTime?->delete();
            } else {
                // 更新または新規作成
                if ($breakTime) {
                    $breakTime->update([
                        'break_start' => $btr->after_start,
                        'break_end'   => $btr->after_end,
                    ]);
                } else {
                    $attendance->breakTimes()->create([
                        'break_start' => $btr->after_start,
                        'break_end'   => $btr->after_end,
                    ]);
                }
            }
        }

        // 申請を承認済みに
        $attendanceRequest->update(['status' => 'approved']);

        return redirect()
            ->route('admin.requests.approve.show', $attendanceRequest->id)
            ->with('success', '承認しました。');
    }
}
