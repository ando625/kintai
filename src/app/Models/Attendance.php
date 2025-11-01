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
        return self::$statusLabels[$this->status];
    }

    //blade側での表示
    //{{ $attendance->status_label }}


    //勤怠は１人のユーザーに属する
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //勤怠には複数の休憩がある
    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    //勤怠には複数の申請がある
    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }
}
