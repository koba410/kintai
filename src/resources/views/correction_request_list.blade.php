@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-left mt-5 mb-5">| 申請一覧</h1>

            <!-- タブ切り替え -->
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link active" id="pending-tab" data-bs-toggle="tab" href="#pending">承認待ち</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="approved-tab" data-bs-toggle="tab" href="#approved">承認済み</a>
                </li>
            </ul>

            <!-- タブコンテンツ -->
            <div class="tab-content mt-4">
                <!-- 承認待ち -->
                <div class="tab-pane fade show active" id="pending">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>状態</th>
                                <th>名前</th>
                                <th>対象日付</th>
                                <th>申請理由</th>
                                <th>申請日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pendingRequests as $request)
                                <tr>
                                    <td>{{ $request->approval_status }}</td>
                                    <td>{{ $request->user->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($request->corrected_date)->format('Y/m/d') }}</td>
                                    <td>{{ $request->reason }}</td>
                                    <td>{{ \Carbon\Carbon::parse($request->created_at)->format('Y/m/d') }}</td>
                                    <td>
                                        <a href="{{ route('attendance.detail', $request->attendance->id) }}"
                                            class="btn btn-primary btn-sm">詳細</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">承認待ちの申請はありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- 承認済み -->
                <div class="tab-pane fade" id="approved">
                    <table class="table table-bordered text-center">
                        <thead>
                            <tr>
                                <th>状態</th>
                                <th>名前</th>
                                <th>対象日付</th>
                                <th>申請理由</th>
                                <th>承認日時</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($approvedRequests as $request)
                                <tr>
                                    <td>{{ $request->approval_status }}</td>
                                    <td>{{ $request->user->name }}</td>
                                    <td>{{ \Carbon\Carbon::parse($request->corrected_date)->format('Y/m/d') }}</td>
                                    <td>{{ $request->reason }}</td>
                                    <td>{{ \Carbon\Carbon::parse($request->updated_at)->format('Y/m/d') }}</td>
                                    <td>
                                        <a href="{{ route('attendance.detail', $request->id) }}"
                                            class="btn btn-primary btn-sm">詳細</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">承認済みの申請はありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
@endsection
