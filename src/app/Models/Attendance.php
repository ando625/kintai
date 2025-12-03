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


    public static $statusLabels = [
        'off_duty' => '勤務外',
        'working' => '出勤中',
        'break' => '休憩中',
        'finished' => '退勤済',
    ];

    public function getStatusLabelAttribute()
    {
        return self::$statusLabels[$this->status] ?? '不明';
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function breakTimeRequests()
    {
        return $this->hasMany(BreakTimeRequest::class);
    }

    public function attendanceRequests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function latestRequest()
    {
        return $this->hasOne(AttendanceRequest::class)->latestOfMany();
    }


    public function getBreakMinutesAttribute()
    {
        return $this->breakTimes->sum(function ($b) {
            if ($b->break_start && $b->break_end) {
                return $b->break_end->diffInMinutes($b->break_start);
            }
            return 0;
        });
    }

    public function getBreakHoursFormattedAttribute()
    {
        if ($this->break_minutes === null || $this->break_minutes === 0) {
            return '';
        }

        $hours = floor($this->break_minutes / 60);
        $minutes = $this->break_minutes % 60;

        return sprintf('%d:%02d', $hours, $minutes);
    }


    public function getWorkMinutesAttribute()
    {
        if ($this->clock_in && $this->clock_out) {
            return $this->clock_out->diffInMinutes($this->clock_in) - $this->break_minutes;
        }
        return null;
    }

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
