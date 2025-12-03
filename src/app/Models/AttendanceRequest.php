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

    public static $statusLabels = [
        'pending' => '承認待ち',
        'approved' => '承認済み',
    ];

    protected $casts = [
        'before_clock_in' => 'datetime',
        'before_clock_out' => 'datetime',
        'after_clock_in' => 'datetime',
        'after_clock_out' => 'datetime',
    ];

    public function getStatusLabelAttribute()
    {
        return self::$statusLabels[$this->status];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function breakTimeRequests()
    {
        return $this->hasMany(BreakTimeRequest::class);
    }
}
