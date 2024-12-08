<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        // 日付取得（リクエストがない場合は今日の日付）
        $currentDate = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::now();

        // 指定日の勤怠データを取得
        $attendances = Attendance::with(['user', 'breaks'])
            ->whereDate('date', $currentDate->format('Y-m-d'))
            ->get();

        return view('admin_attendance_list', [
            'currentDate' => $currentDate,
            'attendances' => $attendances,
        ]);
    }
}
