<?php
declare(strict_types=1);

use App\Http\Controllers\Product\CategoryController;
use Illuminate\Support\Facades\Route;


Route::get('company/{company}/category/create',[CategoryController::class, 'create'])
    ->name('category.create');
Route::post('company/{company}/category',[CategoryController::class, 'store'])
    ->name('category.store');