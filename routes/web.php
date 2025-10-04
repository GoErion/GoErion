<?php

use App\Http\Controllers\Home\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class,'index'])->name('home');
