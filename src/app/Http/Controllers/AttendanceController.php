<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function showForm()
    {
        // 現在のユーザーを取得
        $user = Auth::user();
        // 今日の日付を取得
        $date = Carbon::today();

        // 出勤データを検索
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($attendance) {
            $status = $attendance->attendance_status;
        } else {
            $status = '勤務外';
        }

        return view('attendance_register', compact('status'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $date = Carbon::today(); // 今日の日付
        $now = Carbon::now();   // 現在の日時

        // 今日の出勤データを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        // ボタンのアクションを判定
        $action = $request->input('action');

        switch ($action) {
            case 'start_work': // 出勤
                if (!$attendance) {
                    // 新しい出勤データを作成
                    Attendance::create([
                        'user_id' => $user->id,
                        'check_in' => $now,
                        'date' => $date,
                        'attendance_status' => '出勤中',
                    ]);
                    $status = '出勤中';
                } else {
                    return redirect()->back()->withErrors('すでに出勤済みです。');
                }
                break;

            case 'start_break': // 休憩開始
                if ($attendance && $attendance->attendance_status === '出勤中') {
                    $attendance->attendance_status = '休憩中';
                    $attendance->save();

                    // 休憩データを記録
                    BreakRecord::create([
                        'attendance_id' => $attendance->id,
                        'start_time' => $now,
                    ]);
                    $status = '休憩中';
                } else {
                    return redirect()->back()->withErrors('休憩を開始できません。');
                }
                break;

            case 'end_break': // 休憩終了
                if ($attendance && $attendance->attendance_status === '休憩中') {
                    $attendance->attendance_status = '出勤中';
                    $attendance->save();

                    // 現在の休憩データを更新
                    $break = BreakRecord::where('attendance_id', $attendance->id)
                        ->whereNull('end_time')
                        ->first();
                    if ($break) {
                        $break->end_time = $now;
                        $break->duration = Carbon::parse($break->start_time)->diff($now)->format('%H:%I:%S');
                        $break->save();
                    }
                    $status = '出勤中';
                } else {
                    return redirect()->back()->withErrors('休憩戻りを行えません。');
                }
                break;

            case 'end_work': // 退勤
                if ($attendance && $attendance->attendance_status === '出勤中') {
                    $attendance->attendance_status = '退勤済';
                    $attendance->check_out = $now;
                    $attendance->save();

                    $status = '退勤済';
                    return redirect()->back()->with('message', 'お疲れ様でした。');
                } else {
                    return redirect()->back()->withErrors('退勤できません。');
                }
                break;

            default:
                return redirect()->back()->withErrors('不明な操作です。');
        }

        // ステータスをビューに渡す
        return redirect()->route('attendance.show')->with('status', $status);
    }
}
