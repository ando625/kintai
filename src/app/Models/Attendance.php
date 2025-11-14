<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'status',
        'remarks',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'work_date' => 'date',
    ];


    //status（状態を日本語で扱う）
    public static $statusLabels = [
        'off_duty' => '勤務外',
        'working' => '出勤中',
        'break' => '休憩中',
        'finished' => '退勤済み',
    ];

    //ステータスを日本語で取得できるアクセサ
    public function getStatusLabelAttribute()
    {
        return self::$statusLabels[$this->status] ?? '不明';
    }

    //blade側での表示
    //{{ $attendance->status_label }}


    //勤怠は１人のユーザーに属する
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //勤怠には複数の休憩がある
    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function breakTimeRequests()
    {
        return $this->hasMany(BreakTimeRequest::class);
    }

    //勤怠には複数の申請がある
    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function latestRequest()
    {
        return $this->hasOne(AttendanceRequest::class)->latestOfMany();
    }


    // 休憩時間（合計分数）を計算
    public function getBreakMinutesAttribute()
    {
        // 各休憩の開始・終了時刻から分数を計算して合計
        return $this->breakTimes->sum(function ($b) {
            if ($b->break_start && $b->break_end) {
                return $b->break_end->diffInMinutes($b->break_start);
            }
            return 0;
        });
    }
    // 休憩時間（HH:MM形式で表示）
    public function getBreakHoursFormattedAttribute()
    {
        if ($this->break_minutes === null || $this->break_minutes === 0) {
            return '';
        }

        $hours = floor($this->break_minutes / 60); // 時間
        $minutes = $this->break_minutes % 60;      // 残り分

        return sprintf('%d:%02d', $hours, $minutes);
    }

    /**
     * 🔹 実働時間（分）＝（退勤 - 出勤） - 休憩
     */
    public function getWorkMinutesAttribute()
    {
        if ($this->clock_in && $this->clock_out) {
            return $this->clock_out->diffInMinutes($this->clock_in) - $this->break_minutes;
        }
        return null;
    }

    /**
     * 🔹 実働時間（HH:MM形式）
     */
    public function getWorkHoursFormattedAttribute()
    {
        if ($this->work_minutes === null || $this->work_minutes <= 0) {
            return '';
        }

        $hours = floor($this->work_minutes / 60);
        $minutes = $this->work_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }

}
