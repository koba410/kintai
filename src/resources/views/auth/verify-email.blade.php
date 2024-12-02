@extends('layouts.app')

@section('content')
    <div class="container text-center mt-5">
        <h2>メール認証の確認</h2>
        <p class="mt-5">ご登録のメールアドレスに認証リンクを送信しました。メールを確認し、リンクをクリックして認証を完了してください。</p>
        <p>もしメールが届かない場合は、再度送信してください。</p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary mt-5">認証メールを再送する</button>
        </form>
        <div class="alert alert-warning text-center mt-4 wid-auto" role="alert">
            <strong>注意:</strong> メールアドレス認証を行わないとログインできません。
        </div>
        {{-- <form action="{{ route('guest.view') }}" method="POST" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-primary">ログインせずに商品を閲覧する場合はこちら</button>
        </form> --}}
    </div>
@endsection
