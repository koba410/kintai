<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    // ユーザー登録画面の表示
    public function showRegistrationForm()
    {
        return view('auth.register');
    }


    // ユーザー登録機能
    public function register(RegisterRequest $request)
    {
        // ユーザー作成
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // staffロールを付与
        $user->assignRole('staff');

        // 登録後に自動でログインさせる
        Auth::login($user);

        // メール認証の通知を送信
        event(new Registered($user));

        // 認証ページへリダイレクト
        return redirect()->route('verification.notice');
    }
}
