<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\User\AttendanceController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\User\CheckInController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');



Route::get('/', function () {
    return view('auth.login'); // 一般ユーザー用ログイン画面を表示
});



// 管理者用ルートグループ
Route::prefix('admin')->name('admin.')->group(function (){
        Route::middleware('guest:admin')->group(function () {
            //管理者ログイン画面
            Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
            //管理者ログイン
            Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');
    });

        Route::middleware('auth:admin')->group(function () {
            //管理者ログアウト
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
            // 管理者トップページ（AttendanceControllerを使用）
            Route::get('/index', [AdminAttendanceController::class, 'index'])->name('index');

            //管理者側staff詳細ページ
            Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('staff.show');

            //管理者詳細画面
            Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');

            Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');

            //スタッフ一覧
            Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('staff.list');

            //申請一覧画面
            Route::get('/requests', [AdminAttendanceController::class, 'requests'])->name('requests');

            //修正申請詳細画面
            Route::get('/requests/approve/{id}', [AdminAttendanceController::class, 'showRequest'])->name('requests.approve.show');

            Route::patch('/requests/approve/{id}', [AdminAttendanceController::class, 'approveRequest'])->name('requests.approve.update');

            //CSV
            Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportCsv'])->name('attendance.staff.csv');


    });
});



// ✅ 一般ユーザー用ルート
Route::middleware(['auth:web', 'verified'])->group(function () {

    //勤怠登録画面
    Route::get('/user/check-in', [CheckInController::class, 'index'])->name('user.check-in');
    Route::post('/user/clock-in', [CheckInController::class, 'clockIn'])->name('user.clockIn');
    Route::post('/user/clock-out', [CheckInController::class, 'clockOut'])->name('user.clockOut');
    Route::post('/user/break-start', [CheckInController::class, 'breakStart'])->name('user.breakStart');
    Route::post('/user/break-end', [CheckInController::class, 'breakEnd'])->name('user.breakEnd');
    // 勤怠一覧
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('user.index');
    //勤怠詳細画面
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('user.show');
    //勤怠申請
    Route::post('/attendance/{id}/request', [AttendanceController::class, 'storeRequest'])->name('user.attendance.request.store');
    //申請一覧画面
    Route::get('/my_requests', [AttendanceController::class, 'requests'])->name('user.request.list');
});

// ✅ メール認証
Route::get('email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('user.check-in');
})->middleware(['auth', 'signed'])->name('verification.verify');

// ✅ 再送信
Route::post('/email/verification-notification', function (Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送信しました');
})->middleware(['auth', 'throttle:50,1'])->name('verification.send');

