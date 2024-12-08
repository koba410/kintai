@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-left mt-5">| 勤怠一覧</h1>

        <!-- 月情報 -->
        <!-- 前月ボタン -->
        <div class="d-flex justify-content-center align-items-center mb-4">
            <form method="GET" action="{{ route('attendance.index') }}" class="m-0">
                <input type="hidden" name="month"
                    value="{{ \Carbon\Carbon::parse($currentMonth)->subMonth()->format('Y-m') }}">
                <button class="btn btn-secondary">前月</button>
            </form>
            <!-- カレンダー付き月選択 -->
            <form method="GET" action="{{ route('attendance.index') }}" class="mx-2">
                <div class="d-flex align-items-center">
                    <input type="month" name="month" value="{{ \Carbon\Carbon::parse($currentMonth)->format('Y-m') }}"
                        class="form-control" onchange="this.form.submit()">
                </div>
            </form>
            <!-- 翌月ボタン -->
            <form method="GET" action="{{ route('attendance.index') }}" class="m-0">
                <input type="hidden" name="month"
                    value="{{ \Carbon\Carbon::parse($currentMonth)->addMonth()->format('Y-m') }}">
                <button class="btn btn-secondary">翌月</button>
            </form>
        </div>

        <!-- 勤怠テーブル -->
        <table class="table table-bordered text-center" style="min-height: 70vh;">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach (\Carbon\CarbonPeriod::create(\Carbon\Carbon::parse($currentMonth)->startOfMonth(), \Carbon\Carbon::parse($currentMonth)->endOfMonth()) as $date)
                    @php
                        // 日付の取得
                        $attendance = $attendances->firstWhere('date', $date->format('Y-m-d'));
                    @endphp
                    <tr>
                        <td>{{ $date->isoFormat('M/D (ddd)') }}</td>
                        <td>
                            @php
                                // 勤怠データの取得
                                $checkIn = optional($attendance)->check_in;

                                // 出勤時刻を「H:i」形式で表示
                                echo $checkIn ? \Carbon\Carbon::parse($checkIn)->format('H:i') : '';
                            @endphp

                        </td>
                        <td>
                            @php
                                // 勤怠データの取得
                                $checkOut = optional($attendance)->check_out;

                                // 出勤時刻を「H:i」形式で表示
                                echo $checkOut ? \Carbon\Carbon::parse($checkOut)->format('H:i') : '';
                            @endphp
                        </td>
                        <td>
                            @php
                                $totalBreak = 0;

                                // 休憩時間の計算
                                if ($attendance && $attendance->breaks) {
                                    $totalBreak = $attendance->breaks->reduce(function ($carry, $break) {
                                        if (!empty($break->duration)) {
                                            // 時間文字列（HH:MM:SS）を秒単位に変換
                                            $parts = explode(':', $break->duration);
                                            $seconds = $parts[0] * 3600 + $parts[1] * 60 + ($parts[2] ?? 0);
                                            return $carry + $seconds; // 秒単位を加算
                                        }
                                        return $carry;
                                    }, 0);
                                }

                                // 休憩時間を「時:分」にフォーマット
                                echo $totalBreak > 0 ? gmdate('H:i', $totalBreak) : '';
                            @endphp
                        </td>
                        <td>
                            @if ($attendance)
                                @php
                                    // 出勤時間と退勤時間
                                    $checkIn = optional($attendance)->check_in;
                                    $checkOut = optional($attendance)->check_out;

                                    // 労働時間（合計）の計算
                                    $workDuration = 0;
                                    if ($checkIn && $checkOut) {
                                        // 出勤と退勤の差分（分単位）
                                        $totalMinutes = \Carbon\Carbon::parse($checkIn)->diffInMinutes(
                                            \Carbon\Carbon::parse($checkOut),
                                        );

                                        // 休憩時間（分単位）に変換
                                        $totalBreakMinutes = is_numeric($totalBreak) ? $totalBreak / 60 : 0; // 秒 → 分

                                        // 労働時間から休憩時間（分）を引く
                                        $workDuration = $totalMinutes - $totalBreakMinutes;

                                        // 労働時間が負数にならないようにチェック
                                        $workDuration = max(0, $workDuration);
                                    }

                                    // 労働時間を「時:分」にフォーマット
                                    echo $workDuration > 0 ? gmdate('H:i', $workDuration * 60) : '';
                                @endphp
                            @endif

                        </td>
                        <td>
                            @if ($attendance)
                                <a href="{{ route('attendance.detail', $attendance->id) }}"
                                    class="btn btn-primary btn-sm">詳細</a>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
