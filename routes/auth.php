<?php
declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
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

Route::post('logout', [LoginController::class, 'destroy'])
    ->name('logout');