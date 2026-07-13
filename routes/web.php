<?php

use Illuminate\Support\Facades\Route;

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

/*------------------------
| Auth Routes
|------------------------*/

Route::prefix('auth')->middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'loginPage'])->name('web.auth.login-page');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {

});

Route::get('/', function () {
    return redirect()->route('web.admin.dashboard.index');
});

