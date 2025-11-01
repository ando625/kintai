<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'password',
    ];

    //管理者は複数の申請を受け取る
    public function attendancesRequest()
    {
        return $this->hasMany(AttendanceRequest::class);
    }
}
