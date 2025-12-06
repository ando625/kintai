<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\User\AttendanceController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\User\CheckInController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;


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
    return view('auth.login');
});


Route::prefix('admin')->name('admin.')->group(function (){
        Route::middleware('guest:admin')->group(function () {
            Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
            Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');
    });

        Route::middleware('auth:admin')->group(function () {
            Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
            Route::get('/index', [AdminAttendanceController::class, 'index'])->name('index');
            Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('staff.show');
            Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
            Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])
            ->name('attendance.update');
            Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('staff.list');
            Route::get('/requests', [AdminAttendanceController::class, 'requests'])->name('requests');
            Route::get('/requests/approve/{id}', [AdminAttendanceController::class, 'showRequest'])->name('requests.approve.show');
            Route::patch('/requests/approve/{id}', [AdminAttendanceController::class, 'approveRequest'])->name('requests.approve.update');
            Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportCsv'])->name('attendance.staff.csv');

    });
});



Route::middleware(['auth:web', 'verified'])->group(function () {

    Route::get('/user/check-in', [CheckInController::class, 'index'])->name('user.check-in');
    Route::post('/user/clock-in', [CheckInController::class, 'clockIn'])->name('user.clockIn');
    Route::post('/user/clock-out', [CheckInController::class, 'clockOut'])->name('user.clockOut');
    Route::post('/user/break-start', [CheckInController::class, 'breakStart'])->name('user.breakStart');
    Route::post('/user/break-end', [CheckInController::class, 'breakEnd'])->name('user.breakEnd');
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('user.index');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('user.show');
    Route::post('/attendance/{id}/request', [AttendanceController::class, 'storeRequest'])->name('user.attendance.request.store');
    Route::get('/my_requests', [AttendanceController::class, 'requests'])->name('user.request.list');
});


Route::get('email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('user.check-in');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', '認証メールを再送信しました');
})->middleware(['auth', 'throttle:50,1'])->name('verification.send');

