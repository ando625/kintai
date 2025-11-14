<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_request_id',
        'break_time_id',
        'before_start',
        'before_end',
        'after_start',
        'after_end',
    ];

    public function AttendanceRequest()
    {
        return $this->belongsTo(AttendanceRequest::class);
    }

    public function breakTime()
    {
        return $this->belongsTo(BreakTime::class);
    }
}

