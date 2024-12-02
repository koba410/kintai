@extends('layouts.app')

@section('content')
    <!-- メインコンテンツ -->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6" style="max-width: 680px">
                <h2 class="text-center m-4">会員登録</h2>
                <form method="POST" action="{{ route('register') }}">
                    @csrf
                    <!-- ユーザー名 -->
                    <div class="form-group mb-4">
                        <label for="name">ユーザー名</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                            name="name" value="{{ old('name') }}">
                        @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- メールアドレス -->
                    <div class="form-group mb-4">
                        <label for="email">メールアドレス</label>
                        <input type="text" class="form-control @error('email') is-invalid @enderror" id="email"
                            name="email" value="{{ old('email') }}">
                        @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- パスワード -->
                    <div class="form-group mb-4">
                        <label for="password">パスワード</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- 確認用パスワード -->
                    <div class="form-group mb-5">
                        <label for="password_confirmation">確認用パスワード</label>
                        <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                            id="password_confirmation" name="password_confirmation">
                        @error('password_confirmation')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- 登録ボタン -->
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-danger">登録する</button>
                    </div>

                    <!-- ログインリンク -->
                    <div class="text-center mb-4">
                        <a href="{{ route('login') }}" class="text-primary">ログインはこちら</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
