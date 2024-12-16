@extends('layouts.app')

@section('content')
    <div class="container">
        <h2 class="text-left mt-5 mb-5">| スタッフ一覧</h2>

        <!-- スタッフ一覧テーブル -->
        <table class="table table-bordered text-center">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>メールアドレス</th>
                    <th>月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($staffUsers as $staff)
                    <tr>
                        <!-- ユーザーの名前 -->
                        <td>{{ $staff->name }}</td>

                        <!-- ユーザーのメールアドレス -->
                        <td>{{ $staff->email }}</td>

                        <!-- 詳細ボタン：ユーザーIDを基に月次勤怠一覧へ遷移 -->
                        <td>
                            <a href="{{ route('admin.show.staff.attendance', $staff->id) }}"
                                class="btn btn-primary btn-sm">
                                詳細
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">スタッフ情報がありません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
