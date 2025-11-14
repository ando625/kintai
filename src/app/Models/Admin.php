<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Admin extends Authenticatable
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
