<?php

declare(strict_types=1);

use App\Http\Controllers\Product\BrandController;
use App\Http\Controllers\Product\CategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\UnitController;
use Illuminate\Support\Facades\Route;

// categories
Route::get('company/{company}/category/create', [CategoryController::class, 'create'])
    ->name('category.create');
Route::post('company/{company}/category', [CategoryController::class, 'store'])
    ->name('category.store');
// brands
Route::get('/company/{company}/brands', [BrandController::class, 'index'])
    ->name('brand.index');
Route::get('/company/{company}/brands/create', [BrandController::class, 'create'])
    ->name('brand.create');
Route::post('/company/{company}/brands', [BrandController::class, 'store'])
    ->name('brand.store');
Route::get('/company/{company}/brands/{brand}/edit', [BrandController::class, 'edit'])
    ->scopeBindings()
    ->name('brand.edit');
Route::put('/company/{company}/brands/{brand}', [BrandController::class, 'update'])
    ->scopeBindings()
    ->name('brand.update');
Route::patch('/company/{company}/brands/{brand}/archive', [BrandController::class, 'archive'])
    ->scopeBindings()
    ->name('brand.archive');
// units
Route::get('/company/{company}/units', [UnitController::class, 'index'])
    ->name('unit.index');
Route::get('/company/{company}/units/create', [UnitController::class, 'create'])
    ->name('unit.create');
Route::post('/company/{company}/units', [UnitController::class, 'store'])
    ->name('unit.store');
// product
Route::get('/company/{company}/products', [ProductController::class, 'index'])
    ->name('product.index');
Route::get('/company/{company}/products/create', [ProductController::class, 'create'])
    ->name('product.create');
Route::post('/company/{company}/products', [ProductController::class, 'store'])
    ->name('product.store');
Route::get('company/{company}/products/{product}', [ProductController::class, 'show'])->name('product.show');

Route::get('company/{company}/products/{product}/edit', [ProductController::class, 'edit'])->name('product.edit');
Route::patch('company/{company}/products/{product}', [ProductController::class, 'update'])->name('product.update');
Route::patch('company/{company}/products/{product}/archive', [ProductController::class, 'archive'])->name('product.archive');
Route::patch('company/{company}/products/{product}/restore', [ProductController::class, 'restore'])->name('product.restore');
Route::get('company/{company}/category', [CategoryController::class, 'index'])->name('category.index');
Route::get('company/{company}/category/{category}/edit', [CategoryController::class, 'edit'])->name('category.edit');
Route::patch('company/{company}/category/{category}', [CategoryController::class, 'update'])->name('category.update');
Route::patch('company/{company}/category/{category}/archive', [CategoryController::class, 'archive'])->name('category.archive');
Route::patch('company/{company}/category/{category}/restore', [CategoryController::class, 'restore'])->name('category.restore');
Route::patch('company/{company}/brands/{brand}/restore', [BrandController::class, 'restore'])->name('brand.restore');
Route::get('company/{company}/units/{unit}/edit', [UnitController::class, 'edit'])->name('unit.edit');
Route::patch('company/{company}/units/{unit}', [UnitController::class, 'update'])->name('unit.update');
Route::patch('company/{company}/units/{unit}/archive', [UnitController::class, 'archive'])->name('unit.archive');
Route::patch('company/{company}/units/{unit}/restore', [UnitController::class, 'restore'])->name('unit.restore');
