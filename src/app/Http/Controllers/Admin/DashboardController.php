<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    //管理者専用画面・操作
    public function index()
    {
        return view('admin.index');
    }
}
