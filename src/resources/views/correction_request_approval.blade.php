@extends('layouts.app')

@section('content')
    <div class="container d-flex justify-content-center mt-5">
        <div class="w-75 p-4">
            <h2 class="mb-4">| 勤怠詳細</h2>

            <table class="table table-bordered text-center">
                <tbody>
                    <tr>
                        <th class="w-25">名前</th>
                        <td>{{ $correction->user->name }}</td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>{{ \Carbon\Carbon::parse($correction->corrected_date)->format('Y年m月d日') }}</td>
                    </tr>
                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            {{ \Carbon\Carbon::parse($correction->corrected_check_in)->format('H:i') }} 〜
                            {{ \Carbon\Carbon::parse($correction->corrected_check_out)->format('H:i') }}
                        </td>
                    </tr>
                    @foreach ($correction->breakCorrections as $index => $break)
                        <tr>
                            <th>休憩{{ $index + 1 }}</th>
                            <td>
                                {{ \Carbon\Carbon::parse($break->corrected_start_time)->format('H:i') }} 〜
                                {{ \Carbon\Carbon::parse($break->corrected_end_time)->format('H:i') }}
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <th>備考</th>
                        <td>{{ $correction->reason }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- 承認ボタン -->
            @if ($correction->approval_status === '承認待ち')
                <div class="text-end mt-4">
                    <form method="POST"
                        action="{{ route('admin.approve', ['attendance_correct_request' => $correction->id]) }}">
                        @csrf
                        <button type="submit" class="btn btn-dark px-4 py-1 fs-5">承認</button>
                    </form>
                </div>
            @else
                <div class="text-end mt-4">
                    <button class="btn btn-dark px-4 py-1 fs-5" disabled>承認済み</button>
                </div>
            @endif
        </div>
    </div>
@endsection
