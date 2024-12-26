<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\Attendance;
use App\Models\BreakRecord;
use Illuminate\Http\Request;

class AdminRequestController extends Controller
{
    public function index()
    {
        // 承認待ちの申請データ
        $pendingRequests = AttendanceCorrection::with('user')
            ->where('approval_status', '承認待ち')
            ->orderBy('created_at', 'desc')
            ->get();

        // 承認済みの申請データ
        $approvedRequests = AttendanceCorrection::with('user')
            ->where('approval_status', '承認済み')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin_correction_request_list', compact('pendingRequests', 'approvedRequests'));
    }

    public function approveForm($attendance_correct_request)
    {
        // 申請データを取得
        $correction = AttendanceCorrection::with(['user', 'breakCorrections'])->findOrFail($attendance_correct_request);

        return view('correction_request_approval', compact('correction'));
    }

    public function approve($attendance_correct_request)
    {
        // 申請データ取得
        $correction = AttendanceCorrection::with('attendance')->findOrFail($attendance_correct_request);

        // 当該勤怠データを更新
        $attendance = $correction->attendance;
        $attendance->update([
            'check_in' => $correction->corrected_check_in,
            'check_out' => $correction->corrected_check_out,
        ]);

        // 休憩時間を更新
        foreach ($correction->breakCorrections as $breakCorrection) {
            $break = BreakRecord::findOrFail($breakCorrection->break_id);
            $break->update([
                'start_time' => $breakCorrection->corrected_start_time,
                'end_time' => $breakCorrection->corrected_end_time,
                'duration' => \Carbon\Carbon::parse($breakCorrection->corrected_start_time)
                    ->diff($breakCorrection->corrected_end_time)->format('%H:%I:%S'),
            ]);
        }

        // 修正申請の状態を「承認済み」に更新
        $correction->update(['approval_status' => '承認済み']);

        return redirect()->route('admin.approve.form', $correction)->with('success', '申請を承認しました。');
    }
}
