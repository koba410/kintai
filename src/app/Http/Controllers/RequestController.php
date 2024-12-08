<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceCorrection;


class RequestController extends Controller
{
    public function index()
    {
        // 自分が行った承認待ち申請
        $pendingRequests = AttendanceCorrection::where('user_id', auth()->id())
            ->where('approval_status', '承認待ち')
            ->get();

        // 自分が行った承認済み申請
        $approvedRequests = AttendanceCorrection::where('user_id', auth()->id())
            ->where('approval_status', '承認済み')
            ->get();

        return view('correction_request_list', compact('pendingRequests', 'approvedRequests'));
    }
}
