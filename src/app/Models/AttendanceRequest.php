<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'admin_id',
        'before_clock_in',
        'before_clock_out',
        'after_clock_in',
        'after_clock_out',
        'before_remarks',
        'after_remarks',
        'status'
    ];

    //status日本語表示
    public static $statusLabels = [
        'pending' => '承認待ち',
        'approved' => '承認済み',
    ];

    //取得アクセサ
    public function getStatusLabelAttribute()
    {
        return self::$statusLabels[$this->status];
    }

    //bladeでの表示
    //{{ $attendanceRequest->status_label }}

    //申請は１人のユーザーが出す
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    //申請は一つの勤怠に
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    //申請は一人の管理者が承認
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    //申請は複薄の休憩申請をもつ
    public function breakTimeRequests()
    {
        return $this->hasMany(BreakTimeRequest::class);
    }
}
