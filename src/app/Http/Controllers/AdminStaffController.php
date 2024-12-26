<?php

namespace App\Http\Controllers;

use App\Models\User;

class AdminStaffController extends Controller
{
    // スタッフ一覧画面を表示
    public function index()
    {
        // 一般ユーザー（スタッフ）を取得: 「role」に 'staff' ロールを持つユーザー
        $staffUsers = User::role('staff')->get();

        return view('admin_staff_list', compact('staffUsers'));
    }
}
