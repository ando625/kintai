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

    public function staffAttendance($id, Request $request)
    {
        // ユーザー取得
        $user = User::findOrFail($id);

        // 表示する月を決定
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month'))
            : Carbon::now();

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth   = $currentMonth->copy()->endOfMonth();

        // 勤怠データ取得（日付でキー化）
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

            if ($attendances->has($workDate)) {
                // DBにある日
                $daysInMonth[] = $attendances->get($workDate);
            } else {
                // DBにない日 → 空オブジェクト生成
                $daysInMonth[] = new Attendance([
                    'id' => null, // 詳細ボタン押せないように null
                    'work_date' => $date,
                    'clock_in' => null,
                    'clock_out' => null,
                    'remarks' => null,
                    'breakTimes' => collect([]),
                ]);
            }

            $date->addDay();
        }

        // 前月・翌月リンク
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
        $attendance = Attendance::with(['user', 'breakTimes'])->findOrFail($id);
        $user = $attendance->user ?? new User(['name' => '未登録']);



        // breakTimes を ID順に並べ替え
        $breakTimes = $attendance->breakTimes->sortBy('id')->values()->all();

        // 最低2行保証（DBに1件しかなければ null を追加して2件にする）
        while (count($breakTimes) < 2) {
            $breakTimes[] = (object)[
                'break_start' => null,
                'break_end' => null,
            ];
        }

        return view('admin.show', compact('attendance', 'user', 'breakTimes'));
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
            ->orderBy('created_at', 'desc')
            ->get();

        //承認済み
        $approvedRequests = AttendanceRequest::with('attendance', 'user')
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->get();


        return view('admin.requests', compact('pendingRequests','approvedRequests'));
    }

    public function showRequest($id)
    {
        $attendanceRequest = AttendanceRequest::with(['attendance.user','breakTimeRequests'])->findOrFail($id);

        $user = $attendanceRequest->attendance->user;


        return view('admin.request-approve', compact('attendanceRequest', 'user'));

    }

    // 修正申請承認処理
    public function approveRequest(Request $request, $id)
    {
        // AttendanceRequest を取得（ユーザーと休憩も一緒に）
        $attendanceRequest = AttendanceRequest::with(['breakTimeRequests', 'user'])->findOrFail($id);
        $user = $attendanceRequest->user;

        // 該当ユーザーの勤怠データ取得
        $attendance = $user->attendances()->where('work_date', $attendanceRequest->work_date)->first();

        if ($attendance) {
            // 勤怠本体の更新（必須項目）
            $attendance->clock_in = $attendanceRequest->after_clock_in;
            $attendance->clock_out = $attendanceRequest->after_clock_out;
            $attendance->remarks = $attendanceRequest->after_remarks;
            $attendance->save();

            // 休憩データの更新 or 削除
            foreach ($attendanceRequest->breakTimeRequests as $btr) {
                $breakTime = $attendance->breakTimes()->where('id', $btr->break_time_id)->first();

                // 両方空なら削除
                if (empty($btr->after_start) && empty($btr->after_end)) {
                    if ($breakTime) {
                        $breakTime->delete();
                    }
                } else {
                    // どちらかに値があれば更新 or 新規作成
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
        }

        // 申請ステータスを承認済みに
        $attendanceRequest->status = 'approved';
        $attendanceRequest->save();

        // 承認完了メッセージでリダイレクト
        return redirect()->route('admin.requests-approve')->with('success', '承認しました。');
    }

}
