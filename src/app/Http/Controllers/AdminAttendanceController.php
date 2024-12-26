<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BreakRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Requests\CorrectionRequest;


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

    public function showDetail($id)
    {
        $attendance = Attendance::with('breaks', 'corrections')->findOrFail($id);
        $user = auth()->user();

        // 修正待ちの申請があるか確認
        $hasPendingCorrection = $attendance->corrections->contains('approval_status', '承認待ち');


        return view('admin_attendance_detail', compact('attendance', 'user', 'hasPendingCorrection'));
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
            'approval_status' => '承認済み',
        ]);
        // 勤怠データ (出勤・退勤時間) を直接修正
        $attendance->update([
            'check_in' => $request->input('date') . ' ' . $request->input('check_in') . ':00',
            'check_out' => $request->input('date') . ' ' . $request->input('check_out') . ':00',
        ]);

        // 休憩修正申請を作成
        foreach ($request->input('breaks', []) as $breakData) {
            $correction->breakCorrections()->create([
                'break_id' => $breakData['id'], // 既存の休憩ID
                'attendance_correction_id' => $correction->id,
                'corrected_start_time' => $request->input('date') . ' ' . $breakData['start_time'] . ':00', // 時刻に日付を付与
                'corrected_end_time' => $request->input('date') . ' ' . $breakData['end_time'] . ':00', // 時刻に日付を付与
                'corrected_duration' => Carbon::parse($breakData['start_time'])->diff($breakData['end_time'])->format('%H:%I:%S'),
            ]);
            if (isset($breakData['id'])) {
                // 既存の休憩データを更新
                $break = BreakRecord::findOrFail($breakData['id']);
                $break->update([
                    'start_time' => $request->input('date') . ' ' . $breakData['start_time'] . ':00',
                    'end_time' => $request->input('date') . ' ' . $breakData['end_time'] . ':00',
                    'duration' => Carbon::parse($breakData['start_time'])->diff(Carbon::parse($breakData['end_time']))->format('%H:%I:%S'),
                ]);
            }
        }

        return redirect()->route('admin.attendance.detail', $attendance->id)->with('success', '修正が反映されました。');
    }

    public function showStaffAttendance(Request $request, $id)
    {

        // 指定ユーザーの情報取得
        $user = User::findOrFail($id);

        // 現在の月を取得
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));

        // 月の開始日と終了日
        $startOfMonth = Carbon::parse($currentMonth . '-01')->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonth . '-01')->endOfMonth();

        // 勤怠データを取得（自分の勤怠データ）
        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->orderBy('date')
            ->get();

        return view('admin_staff_attendance_list', compact('user', 'attendances', 'currentMonth'));
    }

    public function exportCsv(Request $request, $id)
    {
        // 指定月の勤怠データを取得
        $currentMonth = $request->input('month', Carbon::now()->format('Y-m'));
        $attendances = Attendance::with('breaks')
            ->where('user_id', $id)
            ->whereBetween('date', [Carbon::parse($currentMonth)->startOfMonth(), Carbon::parse($currentMonth)->endOfMonth()])
            ->orderBy('date', 'asc') // ここで日付順に並び替え
            ->get();

        // CSVデータ作成
        $csvData = [];
        $csvData[] = ['日付', '出勤', '退勤', '休憩', '合計'];
        foreach ($attendances as $attendance) {
            $totalBreak = $attendance->breaks->reduce(function ($carry, $break) {
                if (!empty($break->duration)) {
                    // duration を HH:MM:SS から秒数に変換
                    $parts = explode(':', $break->duration);
                    $seconds = ($parts[0] ?? 0) * 3600 + ($parts[1] ?? 0) * 60 + ($parts[2] ?? 0);
                    return $carry + $seconds;
                }
                return $carry;
            }, 0);

            $workDuration = $attendance->check_in && $attendance->check_out
                ? Carbon::parse($attendance->check_in)->diffInMinutes($attendance->check_out) - ($totalBreak / 60)
                : 0;


            $csvData[] = [
                $attendance->date,
                $attendance->check_in ? Carbon::parse($attendance->check_in)->format('H:i') : '',
                $attendance->check_out ? Carbon::parse($attendance->check_out)->format('H:i') : '',
                $totalBreak > 0 ? gmdate('H:i', $totalBreak * 60) : '',
                $workDuration > 0 ? gmdate('H:i', $workDuration * 60) : '',
            ];
        }

        // CSVを出力
        $fileName = "attendance_{$currentMonth}.csv";
        $headers = ['Content-Type' => 'text/csv'];

        $callback = function () use ($csvData) {
            $file = fopen('php://output', 'w');
            foreach ($csvData as $line) {
                fputcsv($file, $line);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, array_merge($headers, [
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]));
    }
}
