@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-left mt-5 mb-5">| {{ $currentDate->isoFormat('YYYY年MM月DD日の勤怠') }}</h2>

        <!-- 日付変更機能 -->
        <div class="d-flex justify-content-center align-items-center mb-4">
            {{-- 前日 --}}
            <form method="GET" action="{{ route('admin.staffAttendance.list') }}" class="m-0">
                <input type="hidden" name="date" value="{{ $currentDate->copy()->subDay()->format('Y-m-d') }}">
                <button class="btn btn-secondary">前日</button>
            </form>
            <!-- カレンダー選択 -->
            <form method="GET" action="{{ route('admin.staffAttendance.list') }}" class="mx-5">
                <input type="date" name="date" class="form-control mx-4" value="{{ $currentDate->format('Y-m-d') }}"
                    onchange="this.form.submit()">
            </form>
            {{-- 翌日 --}}
            <form method="GET" action="{{ route('admin.staffAttendance.list') }}" class="m-0">
                <input type="hidden" name="date" value="{{ $currentDate->copy()->addDay()->format('Y-m-d') }}">
                <button class="btn btn-secondary">翌日</button>
            </form>
        </div>

        <!-- 勤怠情報一覧 -->
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance->user->name }}</td>
                        <td>{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}
                        </td>
                        <td>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}
                        </td>
                        <td>
                            @php
                                $totalBreak = $attendance->breaks->reduce(function ($carry, $break) {
                                    $start = \Carbon\Carbon::parse($break->start_time);
                                    $end = \Carbon\Carbon::parse($break->end_time);
                                    return $carry + $start->diffInMinutes($end);
                                }, 0);
                                echo $totalBreak > 0 ? gmdate('H:i', $totalBreak * 60) : '';
                            @endphp
                        </td>
                        <td>
                            @php
                                if ($attendance->check_in && $attendance->check_out) {
                                    $workDuration =
                                        \Carbon\Carbon::parse($attendance->check_in)->diffInMinutes(
                                            \Carbon\Carbon::parse($attendance->check_out),
                                        ) - $totalBreak;
                                    echo $workDuration > 0 ? gmdate('H:i', $workDuration * 60) : '';
                                } else {
                                    echo '';
                                }
                            @endphp
                        </td>
                        <td>
                            <a href="{{ route('admin.attendance.detail', $attendance->id) }}"
                                class="btn btn-primary btn-sm">詳細</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">この日の勤怠情報はありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
