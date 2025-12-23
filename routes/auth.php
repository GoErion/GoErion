<?php


use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('login',[LoginController::class,'showLoginForm'])
    ->name('login');

Route::post('login',[LoginController::class,'login'])
    ->name('login.store');

Route::get('register',[RegisterController::class,'showRegister'])
    ->name('register');

Route::post('register',[RegisterController::class,'store'])
    ->name('register.store');

Route::post('logout',[LoginController::class,'logout'])
    ->name('logout');
