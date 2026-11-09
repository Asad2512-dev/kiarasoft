<?php

use App\Http\Controllers as C;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route(auth()->user()->homeRoute()) : view('landing'))->name('home');

Route::middleware(['auth', 'auth.session', 'active'])->group(function () {
    Route::get('profile', [C\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [C\ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [C\ProfileController::class, 'password'])->name('profile.password');

    // ---------------- Super Admin (platform) ----------------
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [C\Admin\DashboardController::class, 'index'])->middleware('can:platform.dashboard')->name('dashboard');
        Route::middleware('can:platform.shops.manage')->group(function () {
            Route::get('shops', [C\Admin\ShopController::class, 'index'])->name('shops.index');
            Route::get('shops/create', [C\Admin\ShopController::class, 'create'])->name('shops.create');
            Route::post('shops', [C\Admin\ShopController::class, 'store'])->name('shops.store');
            Route::get('shops/{shop}', [C\Admin\ShopController::class, 'show'])->name('shops.show');
            Route::get('shops/{shop}/edit', [C\Admin\ShopController::class, 'edit'])->name('shops.edit');
            Route::put('shops/{shop}', [C\Admin\ShopController::class, 'update'])->name('shops.update');
            Route::patch('shops/{shop}/status', [C\Admin\ShopController::class, 'toggle'])->name('shops.toggle');
        });
        Route::middleware('can:platform.signups.manage')->group(function () {
            Route::get('signups', [C\Admin\SignupRequestController::class, 'index'])->name('signups.index');
            Route::post('signups/{signupRequest}/approve', [C\Admin\SignupRequestController::class, 'approve'])->name('signups.approve');
            Route::post('signups/{signupRequest}/reject', [C\Admin\SignupRequestController::class, 'reject'])->name('signups.reject');
        });
        Route::get('users', [C\Admin\UserController::class, 'index'])->middleware('can:platform.users.view')->name('users.index');
        Route::get('activity', [C\ActivityLogController::class, 'platform'])->middleware('can:platform.logs.view')->name('activity');
        Route::middleware('can:system.backups')->group(function () {
            Route::get('backups', [C\Admin\BackupController::class, 'index'])->name('backups.index');
            Route::post('backups', [C\Admin\BackupController::class, 'store'])->name('backups.store');
            Route::get('backups/{backup}/download', [C\Admin\BackupController::class, 'download'])->name('backups.download');
            Route::post('backups/{backup}/test-restore', [C\Admin\BackupController::class, 'testRestore'])->name('backups.test');
        });
        Route::get('system', [C\Admin\SystemController::class, 'index'])->middleware('can:system.health')->name('system');
    });

    // ---------------- Shop workspace ----------------
    Route::get('dashboard', [C\DashboardController::class, 'index'])->middleware('can:dashboard.view')->name('dashboard');

    Route::middleware('can:branches.view')->group(function () {
        Route::get('branches', [C\BranchController::class, 'index'])->name('branches.index');
        Route::get('branches/create', [C\BranchController::class, 'create'])->middleware('can:branches.manage')->name('branches.create');
        Route::post('branches', [C\BranchController::class, 'store'])->middleware('can:branches.manage')->name('branches.store');
        Route::get('branches/{branch}', [C\BranchController::class, 'show'])->name('branches.show');
        Route::get('branches/{branch}/edit', [C\BranchController::class, 'edit'])->middleware('can:branches.manage')->name('branches.edit');
        Route::put('branches/{branch}', [C\BranchController::class, 'update'])->middleware('can:branches.manage')->name('branches.update');
        Route::patch('branches/{branch}/status', [C\BranchController::class, 'toggle'])->middleware('can:branches.manage')->name('branches.toggle');
    });

    Route::middleware('can:employees.view')->group(function () {
        Route::get('employees', [C\EmployeeController::class, 'index'])->name('employees.index');
        Route::middleware('can:employees.manage')->group(function () {
            Route::get('employees/create', [C\EmployeeController::class, 'create'])->name('employees.create');
            Route::post('employees', [C\EmployeeController::class, 'store'])->name('employees.store');
            Route::get('employees/{employee}/edit', [C\EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('employees/{employee}', [C\EmployeeController::class, 'update'])->name('employees.update');
            Route::patch('employees/{employee}/status', [C\EmployeeController::class, 'toggle'])->name('employees.toggle');
            Route::post('employees/{employee}/password', [C\EmployeeController::class, 'resetPassword'])->name('employees.password');
        });
    });

    Route::middleware('can:categories.manage')->group(function () {
        Route::get('categories', [C\CategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [C\CategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [C\CategoryController::class, 'update'])->name('categories.update');
        Route::delete('categories/{category}', [C\CategoryController::class, 'archive'])->name('categories.archive');
        Route::patch('categories/{id}/restore', [C\CategoryController::class, 'restore'])->name('categories.restore');
    });

    Route::middleware('can:products.manage')->group(function () {
        Route::get('products/export', [C\ProductController::class, 'export'])->name('products.export');
        Route::resource('products', C\ProductController::class)->except(['destroy']);
        Route::delete('products/{product}', [C\ProductController::class, 'archive'])->name('products.archive');
        Route::patch('products/{id}/restore', [C\ProductController::class, 'restore'])->name('products.restore');
    });

    Route::middleware('can:inventory.view')->group(function () {
        Route::get('inventory', [C\InventoryController::class, 'index'])->name('inventory.index');
        Route::get('inventory/movements', [C\InventoryController::class, 'movements'])->name('inventory.movements');
        Route::middleware('can:inventory.adjust')->group(function () {
            Route::get('inventory/adjust', [C\InventoryController::class, 'create'])->name('inventory.create');
            Route::post('inventory/adjust', [C\InventoryController::class, 'store'])->name('inventory.store');
            Route::put('inventory/{inventory}/threshold', [C\InventoryController::class, 'threshold'])->name('inventory.threshold');
            Route::get('inventory/reconcile', [C\InventoryController::class, 'reconcile'])->name('inventory.reconcile');
        });
    });

    Route::middleware('can:pos.use')->group(function () {
        Route::get('pos', [C\PosController::class, 'index'])->name('pos.index');
        Route::get('pos/products', [C\PosController::class, 'products'])->name('pos.products');
        Route::post('pos/checkout', [C\PosController::class, 'checkout'])->middleware('throttle:checkout')->name('pos.checkout');
    });

    Route::middleware('can:sales.view')->group(function () {
        Route::get('sales', [C\SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [C\SaleController::class, 'show'])->name('sales.show');
        Route::get('sales/{sale}/receipt', [C\SaleController::class, 'receipt'])->name('sales.receipt');
    });

    Route::middleware('can:returns.create')->group(function () {
        Route::get('returns', [C\ReturnController::class, 'index'])->name('returns.index');
        Route::get('sales/{sale}/return', [C\ReturnController::class, 'create'])->name('returns.create');
        Route::post('sales/{sale}/return', [C\ReturnController::class, 'store'])->name('returns.store');
        Route::get('returns/{saleReturn}', [C\ReturnController::class, 'show'])->name('returns.show');
    });

    Route::middleware('can:reports.view')->group(function () {
        Route::get('reports', [C\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [C\ReportController::class, 'export'])->middleware('can:reports.export')->name('reports.export');
    });

    Route::get('alerts', [C\AlertController::class, 'index'])->middleware('can:alerts.view')->name('alerts.index');
    Route::get('activity', [C\ActivityLogController::class, 'index'])->middleware('can:logs.view')->name('activity.index');

    Route::middleware('can:settings.manage')->group(function () {
        Route::get('settings', [C\SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [C\SettingsController::class, 'update'])->name('settings.update');
    });
});
