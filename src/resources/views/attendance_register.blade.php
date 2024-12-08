@extends('layouts.app')

@section('content')
    <div class="container d-flex flex-column align-items-center justify-content-center text-center" style="min-height: 70vh;">

        <!-- ステータス表示 -->
        <div class="mb-3">
            <span class="badge badge-secondary" id="status">
                {{ $status ?? '勤務外' }}
            </span>
        </div>

        <!-- 日時情報 -->
        <h3 id="current-date" class="mb-4">{{ now()->isoFormat('Y年M月D日 (ddd)') }}</h2>
        <h1 id="current-time" class="mb-5">{{ now()->format('H:i') }}</h1>

        <!-- ボタン表示 -->
        <form id="attendance-form" method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="status" id="form-status" value="{{ $status ?? '勤務外' }}">
            <input type="hidden" name="action" id="form-action" value="">

            @if ($status === '勤務外')
                <button class="btn btn-primary btn-lg" id="start-work" data-action="start_work">出勤</button>
            @elseif ($status === '出勤中')
                <button class="btn btn-warning btn-lg mr-3" id="start-break" data-action="start_break"
                    style="margin-right: 50px;">休憩</button>
                <button class="btn btn-danger btn-lg" id="end-work" data-action="end_work">退勤</button>
            @elseif ($status === '休憩中')
                <button class="btn btn-success btn-lg" id="end-break" data-action="end_break">休憩戻</button>
            @elseif ($status === '退勤済')
                <h4>お疲れ様でした。</p>
            @endif
        </form>
    </div>

    <!-- JavaScript for Dynamic Updates -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // フォームと隠しフィールドを取得
            const form = document.getElementById('attendance-form');
            const actionInput = document.getElementById('form-action');

            // 全てのボタンにクリックイベントを設定
            document.querySelectorAll('button[data-action]').forEach(button => {
                button.addEventListener('click', (event) => {
                    event.preventDefault(); // フォームのデフォルト送信を防ぐ
                    const action = button.getAttribute('data-action'); // ボタンのアクションを取得
                    actionInput.value = action; // フォームにアクションをセット
                    form.submit(); // フォーム送信
                });
            });
        });
    </script>
@endsection
