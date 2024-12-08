<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Requests\CorrectionRequest;


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

    public function index(Request $request)
    {
        // 現在の月を取得
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));

        // 月の開始日と終了日
        $startOfMonth = Carbon::parse($currentMonth . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonth . '-01')->endOfMonth();

        // 勤怠データを取得（自分の勤怠データ）
        $attendances = Attendance::where('user_id', auth()->id())
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date')
            ->get();

        return view('attendance_list', [
            'attendances' => $attendances,
            'currentMonth' => $currentMonth,
        ]);
    }

    public function showDetail($id)
    {
        $attendance = Attendance::with('breaks', 'corrections')->findOrFail($id);
        $user = auth()->user();

        // 修正待ちの申請があるか確認
        $hasPendingCorrection = $attendance->corrections->contains('approval_status', '承認待ち');


        return view('attendance_detail', compact('attendance', 'user', 'hasPendingCorrection'));
    }

    public function correction(CorrectionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // 修正申請を作成
        $correction = $attendance->corrections()->create([
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'corrected_date' => $request->input('date'),
            'corrected_check_in' => $request->input('date') . ' ' . $request->input('check_in') . ':00', // 時刻に日付を付与
            'corrected_check_out' => $request->input('date') . ' ' . $request->input('check_out') . ':00', // 時刻に日付を付与
            'reason' => $request->input('note'),
            'approval_status' => '承認待ち',
        ]);

        // 休憩修正申請を作成
        foreach ($request->input('breaks', []) as $break) {
            $correction->breakCorrections()->create([
                'break_id' => $break['id'], // 既存の休憩ID
                'attendance_correction_id' => $correction->id,
                'corrected_start_time' => $request->input('date') . ' ' . $break['start_time'] . ':00', // 時刻に日付を付与
                'corrected_end_time' => $request->input('date') . ' ' . $break['end_time'] . ':00', // 時刻に日付を付与
                'corrected_duration' => Carbon::parse($break['start_time'])->diff($break['end_time'])->format('%H:%I:%S'),
            ]);
        }

        return redirect()->route('attendance.detail', $attendance->id)->with('success', '修正申請が送信されました。');
    }
}
