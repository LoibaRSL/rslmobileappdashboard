<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WSO2Controller;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\WSO2AuthController;
use App\Http\Controllers\Admin\BusinessRegistrationController;
use App\Http\Controllers\TinRegistrationController;
use App\Http\Controllers\BusinessRegistrationController as PublicBusinessRegistrationController;
use App\Http\Controllers\BusinessAmendmentController;
use App\Http\Controllers\TinRegistrationsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AmendmentsController;
use App\Http\Controllers\ReturnsController;
use App\Http\Controllers\ReportsController;

// WSO2 Authentication Routes
Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('wso2', [WSO2AuthController::class, 'redirectToWSO2'])->name('wso2.login');
    Route::get('wso2/callback', [WSO2AuthController::class, 'handleWSO2Callback'])->name('wso2.callback');
    Route::post('logout', [WSO2AuthController::class, 'logout'])->name('logout');
});

// Login route (redirects to WSO2)
Route::get('/login', function () {
    return redirect()->route('auth.wso2.login');
})->name('login');

Route::get('/', function () {
    return view('auth-split.sign-in');
});

// Protected Routes (require WSO2 authentication)
Route::middleware(['wso2.auth'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
    Route::get('/dashboard/stats', [DashboardController::class, 'getStats'])->name('dashboard.stats');
    
    // TIN Individual Routes
    Route::prefix('tin/individual')->name('tin.individual.')->group(function () {
        Route::get('/create', [TinRegistrationController::class, 'create'])->name('create');
        Route::post('/store', [TinRegistrationController::class, 'store'])->name('store');
        Route::get('/pending', [TinRegistrationController::class, 'pending'])->name('pending');
        Route::get('/approved', [TinRegistrationController::class, 'approved'])->name('approved');
        Route::get('/rejected', [TinRegistrationController::class, 'rejected'])->name('rejected');
        Route::get('/{id}', [TinRegistrationController::class, 'show'])->name('show');
    });

    // TIN Business Routes (admin Views)
Route::prefix('tin/business')->name('tin.business.')->group(function () {
    // Use Web controller for views
    Route::get('/', [App\Http\Controllers\admin\BusinessRegistrationController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\admin\BusinessRegistrationController::class, 'create'])->name('create');
    Route::get('/pending', [App\Http\Controllers\admin\BusinessRegistrationController::class, 'pending'])->name('pending');
    Route::get('/approved', [App\Http\Controllers\admin\BusinessRegistrationController::class, 'approved'])->name('approved');
    Route::get('/rejected', [App\Http\Controllers\admin\BusinessRegistrationController::class, 'rejected'])->name('rejected');
    Route::get('/my-applications', [App\Http\Controllers\admin\BusinessRegistrationController::class, 'myApplications'])->name('my-applications');
    Route::get('/{id}', [App\Http\Controllers\admin\BusinessRegistrationController::class, 'show'])->name('show');
});
        
    // Amendments Routes
    Route::prefix('amendments')->name('amendments.')->group(function () {
        Route::get('/individual', [AmendmentsController::class, 'individual'])->name('individual');
        Route::get('/business', [AmendmentsController::class, 'business'])->name('business');
        Route::get('/graduation', [AmendmentsController::class, 'graduation'])->name('graduation');
        Route::post('/store', [BusinessAmendmentController::class, 'store'])->name('store');
    });
    
    // Returns Routes
    Route::prefix('returns')->name('returns.')->group(function () {
        Route::get('/resident-tax', [ReturnsController::class, 'residentTax'])->name('resident-tax');
        Route::get('/non-resident-tax', [ReturnsController::class, 'nonResidentTax'])->name('non-resident-tax');
        Route::get('/vat', [ReturnsController::class, 'vat'])->name('vat');
    });
    
    // Reports Routes
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::prefix('registration')->name('registration.')->group(function () {
            Route::get('/individual', [ReportsController::class, 'registrationIndividual'])->name('individual');
            Route::get('/business', [ReportsController::class, 'registrationBusiness'])->name('business');
            Route::get('/export', [ReportsController::class, 'exportRegistration'])->name('export');
        });
        Route::prefix('amendments')->name('amendments.')->group(function () {
            Route::get('/individual', [ReportsController::class, 'amendmentsIndividual'])->name('individual');
            Route::get('/business', [ReportsController::class, 'amendmentsBusiness'])->name('business');
            Route::get('/graduation', [ReportsController::class, 'amendmentsGraduation'])->name('graduation');
        });
    });
    
    // Admin routes with permission checks
    Route::prefix('admin')->name('admin.')->middleware(['check.permission:view_dashboard'])->group(function () {
        
        // User Management Routes
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::post('/{user}/assign-roles', [UserManagementController::class, 'assignRoles'])->name('assign-roles');
            Route::post('/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
            Route::post('/sync-wso2', [UserManagementController::class, 'syncFromWSO2'])->name('sync-wso2');
        }); 
        
        // Role Management Routes
        Route::resource('roles', RoleManagementController::class);
         
        // Business Registration Management Routes
        Route::prefix('registrations')->name('registrations.')->group(function () {
            Route::get('/', [BusinessRegistrationController::class, 'index'])->name('index');
            Route::get('/pending', [BusinessRegistrationController::class, 'pending'])->name('pending');
            Route::get('/approved', [BusinessRegistrationController::class, 'approved'])->name('approved');
            Route::get('/rejected', [BusinessRegistrationController::class, 'rejected'])->name('rejected');
            Route::get('/{id}', [BusinessRegistrationController::class, 'show'])->name('show');
            Route::post('/{id}/approve', [BusinessRegistrationController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [BusinessRegistrationController::class, 'reject'])->name('reject');
            Route::post('/bulk-approve', [BusinessRegistrationController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('/bulk-reject', [BusinessRegistrationController::class, 'bulkReject'])->name('bulk-reject');
            Route::get('/export/csv', [BusinessRegistrationController::class, 'export'])->name('export');     
        });
    });
});

// Public API Routes (No authentication required)
Route::post('/register-tin', [TinRegistrationController::class, 'register']);
Route::post('/business-registration', [PublicBusinessRegistrationController::class, 'store']);
Route::get('/business-registrations', [PublicBusinessRegistrationController::class, 'index']);
Route::get('/business-registration/{id}', [PublicBusinessRegistrationController::class, 'show']);
Route::post('/amend', [BusinessAmendmentController::class, 'store']);
Route::post('/register', [TinRegistrationsController::class, 'register']);
Route::post('/verify-email', [TinRegistrationsController::class, 'verifyEmail']);
Route::get('/status/{ref}', [TinRegistrationsController::class, 'checkStatus']);
Route::get('/registration/{id}', [TinRegistrationsController::class, 'getRegistration']);
Route::get('/user-data/{tin}', [TinRegistrationsController::class, 'getUserDataByTin']);
Route::post('/amend', [TinRegistrationsController::class, 'amend']);
Route::get('/check-amendment/{tin}', [TinRegistrationsController::class, 'checkPendingAmendment']);

// Static Routes (UI Views)
Route::get('/auth/delete-account', function () {
    return view('auth.delete-account');
});

Route::get('/auth/reset-pass', function () {
    return view('auth.reset-pass');
});

Route::get('/auth/success-mail', function () {
    return view('auth.success-mail');
});

Route::get('/auth/two-factor', function () {
    return view('auth.two-factor');
});

Route::get('/auth/login-pin', function () {
    return view('auth.login-pin');
});

Route::get('/layouts/compact', function () {
    return view('layouts.compact');
});

Route::get('/layouts/boxed', function () {
    return view('layouts.boxed');
});

Route::get('/layouts/horizontal', function () {
    return view('layouts.horizontal');
});

Route::get('/layouts/preloader', function () {
    return view('layouts.preloader');
});

Route::get('/layouts/scrollable', function () {
    return view('layouts.scrollable');
});

Route::get('/error/400', function () {
    return view('error.400');
});

Route::get('/error/500', function () {
    return view('error.500');
});

Route::get('/error/408', function () {
    return view('error.408');
});

Route::get('/error/404', function () {
    return view('error.404');
});

Route::get('/error/401', function () {
    return view('error.401');
});

Route::get('/error/maintenance', function () {
    return view('error.maintenance');
});

Route::get('/error/403', function () {
    return view('error.403');
});

Route::get('/icons/lucide', function () {
    return view('icons.lucide');
});

Route::get('/auth-split/lock-screen', function () {
    return view('auth-split.lock-screen');
});

Route::get('/auth-split/sign-in', function () {
    return view('auth-split.sign-in');
});

Route::get('/auth-split/new-pass', function () {
    return view('auth-split.new-pass');
});

Route::get('/auth-split/delete-account', function () {
    return view('auth-split.delete-account');
});

Route::get('/auth-split/sign-up', function () {
    return view('auth-split.sign-up');
});

Route::get('/auth-split/reset-pass', function () {
    return view('auth-split.reset-pass');
});

Route::get('/auth-split/success-mail', function () {
    return view('auth-split.success-mail');
});

Route::get('/auth-split/two-factor', function () {
    return view('auth-split.two-factor');
});

Route::get('/auth-split/login-pin', function () {
    return view('auth-split.login-pin');
});

Route::get('/pages/empty', function () {
    return view('pages.empty');
});

Route::get('/layouts/sidebar/dark', function () {
    return view('layouts.sidebar.dark');
});

Route::get('/layouts/sidebar/offcanvas', function () {
    return view('layouts.sidebar.offcanvas');
});

Route::get('/layouts/sidebar/no-icons', function () {
    return view('layouts.sidebar.no-icons');
});

Route::get('/layouts/sidebar/compact', function () {
    return view('layouts.sidebar.compact');
});

Route::get('/layouts/sidebar/image', function () {
    return view('layouts.sidebar.image');
});

Route::get('/layouts/sidebar/on-hover', function () {
    return view('layouts.sidebar.on-hover');
});

Route::get('/layouts/sidebar/gray', function () {
    return view('layouts.sidebar.gray');
});

Route::get('/layouts/sidebar/gradient', function () {
    return view('layouts.sidebar.gradient');
});

Route::get('/layouts/sidebar/on-hover-active', function () {
    return view('layouts.sidebar.on-hover-active');
});

Route::get('/layouts/sidebar/with-lines', function () {
    return view('layouts.sidebar.with-lines');
});

Route::get('/layouts/topbar/gray', function () {
    return view('layouts.topbar.gray');
});

Route::get('/layouts/topbar/gradient', function () {
    return view('layouts.topbar.gradient');
});

Route::get('/layouts/topbar/light', function () {
    return view('layouts.topbar.light');
});