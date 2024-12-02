<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    // ログイン画面の表示
    public function showLoginForm()
    {
        return view('auth.login');
    }


    // ログイン機能
    public function login(LoginRequest $request)
    {

        // 認証処理
        if (Auth::attempt($request->only('email', 'password'))) {
            // 認証成功

            /** @var User $user */
            $user = Auth::user();

            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                return back()->with('status', 'メール認証が必要です。');
            }

            return redirect()->intended('/');
        }

        // 認証失敗時の処理
        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません。',
        ])->withInput($request->only('email'));
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウト後に/loginにリダイレクト
        return redirect('/login');
    }
}
