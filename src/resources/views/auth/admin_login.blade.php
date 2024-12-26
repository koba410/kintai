@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6" style="max-width: 680px">
                <h2 class="text-center mt-5 mb-4">管理者ログイン</h2>
                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf

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
                    <div class="form-group mb-5">
                        <label for="password">パスワード</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                            name="password">
                        @error('password')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        @enderror
                    </div>

                    <!-- ログインボタン -->
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-danger">ログインする</button>
                    </div>
                    @if (session('status'))
                        <div class="alert alert-warning">
                            {{ session('status') }}
                        </div>
                    @endif
                </form>
                
            </div>
        </div>
    </div>
@endsection
