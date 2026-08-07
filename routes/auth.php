<?php
declare(strict_types=1);

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::get('register',[RegisterController::class,'create'])
    ->name('register.create');
Route::post('register', [RegisterController::class, 'store'])
    ->name('register');