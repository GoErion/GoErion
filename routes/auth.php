<?php
declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('login',[LoginController::class,'create'])
    ->name('login.create');
Route::post('login', [LoginController::class, 'store'])
    ->name('login');

Route::get('register',[RegisterController::class,'create'])
    ->name('register.create');
Route::post('register', [RegisterController::class, 'store'])
    ->name('register');

Route::get('forgot-password',[PasswordResetController::class,'create'])
    ->name('password.request');
Route::post('forgot-password', [PasswordResetController::class, 'store'])
    ->name('password.email');

Route::get('reset-password/{token}',[NewPasswordController::class,'create'])
    ->name('password.reset');
Route::post('reset-password', [NewPasswordController::class, 'store'])
    ->name('password.store');

Route::post('logout', [LoginController::class, 'destroy'])
    ->name('logout');