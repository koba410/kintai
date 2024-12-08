<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    // ログイン画面の表示
    public function index()
    {
        return view('admin_attendance_list');
    }
}
