<?php

declare(strict_types=1);

use App\Http\Controllers\Company\BranchController;
use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\Company\CompanyMemberController;
use App\Http\Controllers\Company\CompanyMembershipController;
use App\Http\Controllers\Company\CompanyOwnershipController;
use App\Http\Controllers\Company\CompanyRoleController;
use App\Http\Controllers\Company\SettingController;
use App\Http\Controllers\Company\WarehouseController;
use App\Http\Controllers\Company\WarehouseLocationController;
use Illuminate\Support\Facades\Route;

// company
Route::get('company', [CompanyController::class, 'create'])
    ->name('company.create');
Route::post('company', [CompanyController::class, 'store'])
    ->name('company.store');
Route::get('company/{company}/edit', [CompanyController::class, 'edit'])
    ->name('company.edit');
Route::patch('company/{company}', [CompanyController::class, 'update'])
    ->name('company.update');

Route::get('company/{company}/members', [CompanyMemberController::class, 'index'])->name('company.members.index');
Route::put('company/{company}/members/{memberUuid}/roles', [CompanyMemberController::class, 'updateRoles'])->name('company.members.roles.update');
Route::get('company/{company}/members/{memberUuid}/remove', [CompanyMemberController::class, 'confirmRemoval'])->name('company.members.remove');
Route::delete('company/{company}/members/{memberUuid}', [CompanyMemberController::class, 'destroy'])->name('company.members.destroy');
Route::get('company/{company}/membership/leave', [CompanyMembershipController::class, 'confirm'])->name('company.membership.leave');
Route::delete('company/{company}/membership', [CompanyMembershipController::class, 'destroy'])->name('company.membership.destroy');

Route::get('company/{company}/roles', [CompanyRoleController::class, 'index'])->name('company.roles.index');
Route::get('company/{company}/roles/create', [CompanyRoleController::class, 'create'])->name('company.roles.create');
Route::post('company/{company}/roles', [CompanyRoleController::class, 'store'])->name('company.roles.store');
Route::get('company/{company}/roles/{roleUuid}/edit', [CompanyRoleController::class, 'edit'])->name('company.roles.edit');
Route::put('company/{company}/roles/{roleUuid}', [CompanyRoleController::class, 'update'])->name('company.roles.update');
Route::delete('company/{company}/roles/{roleUuid}', [CompanyRoleController::class, 'destroy'])->name('company.roles.destroy');

Route::get('company/{company}/owners/{memberUuid}/promote', [CompanyOwnershipController::class, 'confirmPromotion'])->name('company.owners.promote');
Route::post('company/{company}/owners/{memberUuid}', [CompanyOwnershipController::class, 'store'])->name('company.owners.store');
Route::get('company/{company}/owners/{memberUuid}/demote', [CompanyOwnershipController::class, 'confirmDemotion'])->name('company.owners.demote');
Route::delete('company/{company}/owners/{memberUuid}', [CompanyOwnershipController::class, 'destroy'])->name('company.owners.destroy');
// branch
Route::get('company/{company}/branch/create', [BranchController::class, 'create'])
    ->name('branch.create');
Route::post('company/{company}/branch', [BranchController::class, 'store'])
    ->name('branch.store');
// warehouse
Route::get('company/{company}/warehouse/create', [WarehouseController::class, 'create'])
    ->name('warehouse.create');
Route::post('company/{company}/warehouse', [WarehouseController::class, 'store'])
    ->name('warehouse');
// warehouse locations
Route::get('warehouse/{warehouse}/location/create', [WarehouseLocationController::class, 'create'])
    ->name('location.create');
Route::post('warehouse/{warehouse}/location', [WarehouseLocationController::class, 'store'])
    ->name('location');
// settings
Route::get('company/{company}/settings/edit', [SettingController::class, 'edit'])
    ->name('settings.edit');
Route::put('company/{company}/settings', [SettingController::class, 'update'])
    ->name('settings');
