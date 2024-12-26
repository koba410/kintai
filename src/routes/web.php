<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\RequestController;

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminRequestController;




/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// スタッフ
// メールアドレス認証メールの認証ボタン押下後、反映させるために必要なルート
Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
    ->middleware(['auth', 'signed', 'throttle:6,1'])
    ->name('verification.verify');
//メール認証通知ページ用のビュー
Route::get('/email/verify', [VerificationController::class, 'show'])
    ->middleware('auth')->name('verification.notice');
// 認証メール再送信
Route::post('/email/verification-notification', [VerificationController::class, 'send'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::get('register', [RegisterController::class, 'showRegistrationForm'])->name('register.view');
Route::post('register', [RegisterController::class, 'register'])->name('register');

Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::get('logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['role:staff'])->middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'showForm'])->name('attendance.show');
    Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/attendance/{id}', [AttendanceController::class, 'showDetail'])->name('attendance.detail');
    Route::post('/attendance/{id}', [AttendanceController::class, 'correction'])->name('attendance.correction');
    Route::get('/stamp_correction_request/list', [RequestController::class, 'index'])->name('request.index');
});

// 管理者
Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.loginForm');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login');
Route::get('/admin/logout', [AdminAuthController::class, 'destroy'])->name('admin.logout');

Route::middleware(['role:admin'])->middleware('auth')->group(function () {
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index'])->name('admin.staffAttendance.list');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'showDetail'])->name('admin.attendance.detail');
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'correction'])->name('admin.attendance.correction');
    Route::get('/admin/staff/list',[AdminStaffController::class, 'index'])->name('admin.staff.list');
    Route::get('/admin/attendance/staff/{id}',[AdminAttendanceController::class, 'showStaffAttendance'])->name('admin.show.staff.attendance');
    Route::get('/admin/attendance/staff/{id}/csv',[AdminAttendanceController::class, 'exportCsv'])->name('admin.exportCsv');
    Route::get('/admin/stamp_correction_request/list', [AdminRequestController::class, 'index'])->name('admin.requests.index');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminRequestController::class, 'approveForm'])->name('admin.approve.form');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request}', [AdminRequestController::class, 'approve'])->name('admin.approve');
});
