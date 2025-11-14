<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end'   => 'datetime',
    ];


    //休憩は一つの勤怠に属する
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    //休憩は複数の修正申請あり
    public function breakTimeRequests()
    {
        return $this->hasMany(BreakTimeRequest::class);
    }
}
