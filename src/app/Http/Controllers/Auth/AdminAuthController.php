<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\LoginRequest;

class AdminAuthController extends Controller
{
    // ログイン画面の表示
    public function showLoginForm()
    {
        return view('auth.admin_login');
    }


    // ログイン機能
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            /** @var User $user */
            $user = Auth::user();

            // メール認証チェック
            if (!$user->hasVerifiedEmail()) {
                Auth::logout();
                return redirect()->route('admin.loginForm')->with('status', 'メール認証を完了してください。');
            }

            // ロールチェック
            if ($user->hasRole('admin')) {
                return redirect()->route('admin.staffAttendance.list'); // スタッフダッシュボード
            }

            // 不正なロール
            Auth::logout();
            return redirect()->route('admin.loginForm')->with('status', '管理者専用のアカウントでログインしてください。');
        }

        // 認証失敗
        return redirect()->route('admin.loginForm')->with('status', 'ログイン情報が正しくありません。');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウト後に/loginにリダイレクト
        return redirect()->route('admin.loginForm');
    }
}
