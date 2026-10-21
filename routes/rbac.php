<?php

use App\Modules\Rbac\Controllers\AccountConfigController;
use App\Modules\Rbac\Controllers\PermissionMatrixController;
use App\Modules\Rbac\Controllers\RbacAuditController;
use App\Modules\Rbac\Controllers\RoleManagementController;
use App\Modules\Rbac\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('rbac')->name('rbac.')->group(function () {
    // 1. User Management
    Route::get('/users/export', [UserManagementController::class, 'export'])->name('users.export');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/revoke-sessions', [UserManagementController::class, 'revokeSessions'])->name('users.revoke-sessions');
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

    // 2. Role Management
    Route::get('/roles', [RoleManagementController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleManagementController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleManagementController::class, 'store'])->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleManagementController::class, 'edit'])->name('roles.edit');
    Route::put('/roles/{role}', [RoleManagementController::class, 'update'])->name('roles.update');
    Route::post('/roles/{role}/duplicate', [RoleManagementController::class, 'duplicate'])->name('roles.duplicate');
    Route::delete('/roles/{role}', [RoleManagementController::class, 'destroy'])->name('roles.destroy');

    // 3. Permission Matrix
    Route::get('/matrix', [PermissionMatrixController::class, 'index'])->name('matrix.index');
    Route::post('/matrix/{role}', [PermissionMatrixController::class, 'update'])->name('matrix.update');
    Route::post('/matrix/{targetRole}/copy', [PermissionMatrixController::class, 'copy'])->name('matrix.copy');

    // 4. Account Targets Configuration
    Route::get('/config/targets', [AccountConfigController::class, 'index'])->name('config.targets');
    Route::post('/config/targets', [AccountConfigController::class, 'update'])->name('config.targets.update');

    // 5. Audit Trail
    Route::get('/audit', [RbacAuditController::class, 'index'])->name('audit.index');
});
