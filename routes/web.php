<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OutletController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceOutletController;
use App\Http\Controllers\PaymentGatewaySettingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\MailServerController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\EwalletController;
use App\Http\Controllers\GatewayController;
use App\Http\Controllers\Admin\EwalletAdminController;
use App\Http\Controllers\Admin\TopupPackageController;
use App\Http\Controllers\Api\DeviceStatusController;
use App\Http\Controllers\Admin\LogoController;
use App\Http\Controllers\CustomerOutletController;
use App\Http\Controllers\CustomerDeviceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DeviceTransactionController;
use App\Http\Controllers\Admin\DeviceTransactionAdminController;
use App\Http\Controllers\Admin\PaymentGatewayAdminController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Admin\EwalletTransactionAdminController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Admin\TermsOfServiceController;
use App\Http\Controllers\MerchantConfigController;

Route::middleware('guest')->group(function () {
    Route::get('/device/{serial}', [DeviceController::class, 'scan'])->name('device.scan');
    Route::post('/guest/payment/initiate', [PaymentController::class, 'initiateQRPayment'])->name('guest.payment.initiate');
    Route::post('/guest/payment/ewallet', [PaymentController::class, 'payWithEwallet'])->name('guest.payment.ewallet');
    Route::get('/guest/payment/confirm/{deviceOutlet}', [PaymentController::class, 'confirmQR'])->name('guest.payment.confirm');
    Route::post('/guest/payment/callback', [PaymentController::class, 'callback'])
        ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('guest.payment.callback');
    Route::match(['get','post'], '/guest/payment/return', [PaymentController::class, 'returnQRPayment'])
        ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('guest.payment.return');
    Route::get('/device/{transaction}/start', [CustomerDeviceController::class, 'startQRDevice'])->name('guest.devices.start');
});

/*
|--------------------------------------------------------------------------
| Customer Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->group(function () {
    // Public auth routes
    Route::get('/login', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
    Route::post('/login', [CustomerAuthController::class, 'login'])->name('customer.login.submit');
    //Route::post('logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
    Route::match(['get','post'], '/logout', function () {
        Auth::guard('customer')->logout();
        return redirect('/customer/login');
    })->name('customer.logout');

    Route::get('register', [CustomerAuthController::class, 'showRegistrationForm'])->name('customer.register');
    Route::post('register', [CustomerAuthController::class, 'register'])->name('customer.register.submit');

    Route::get('reset', [CustomerAuthController::class, 'showLinkRequestForm'])->name('customer.request');
    Route::post('email', [CustomerAuthController::class, 'sendResetLinkEmail'])->name('customer.email');
    Route::get('reset/{token}', [CustomerAuthController::class, 'showResetForm'])->name('customer.reset');
    Route::post('reset', [CustomerAuthController::class, 'reset'])->name('customer.update');

    Route::get('/ewallet', [EwalletController::class, 'dashboard'])->name('ewallet.dashboard');
    Route::get('/customer/topup/list', [EwalletController::class, 'viewTopupPackage'])->name('ewallet.topuplist');
    Route::post('/ewallet/topup/initiate', [EwalletController::class, 'initiateTopup'])->name('ewallet.topup.initiate');
    Route::get('/ewallet/transaction', [EwalletController::class, 'transactionList'])->name('customer.transaction');
    // Customer return page after payment
    Route::match(['get','post'], '/gateway/return', [GatewayController::class, 'return'])
        ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('gateway.return');
    // Gateway server-to-server callback
    Route::post('/gateway/callback', [GatewayController::class, 'callback'])
        ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('gateway.callback');
    //nearby devices
    Route::get('/customer/outlet/nearby', [CustomerOutletController::class, 'nearby'])
        ->name('customer.outlet.nearby');
    Route::post('/customer/outlet/detect', [CustomerOutletController::class, 'detect'])
        ->name('customer.outlet.detect');
    Route::get('/customer/devices', [CustomerDeviceController::class, 'index'])
        ->name('customer.devices.index');
    Route::get('/customer/outlet/select', [CustomerOutletController::class, 'select'])
        ->name('customer.outlet.select');
    Route::post('/customer/outlet/set', [CustomerOutletController::class, 'setOutlet'])
        ->name('customer.outlet.set');
    //device payment
    Route::post('/customer/payment/initiate', [PaymentController::class, 'initiateDevicePayment'])->name('customer.payment.initiate');
    Route::get('/customer/payment/confirm/{deviceOutlet}', [PaymentController::class, 'confirm'])
        ->name('customer.payment.confirm');
    Route::post('/customer/payment/ewallet', [PaymentController::class, 'payWithEwallet'])->name('customer.payment.ewallet');
    Route::post('/customer/payment/callback', [PaymentController::class, 'callback'])
        ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('customer.payment.callback');
    Route::match(['get','post'], '/customer/payment/return', [PaymentController::class, 'return'])
        ->withoutMiddleware([Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('customer.payment.return');
    Route::get('/customer/device/{transaction}/start', [CustomerDeviceController::class, 'start'])->name('customer.devices.start');
    Route::get('/legals', [CustomerAuthController::class, 'legals'])->name('legals');
    // Protected customer routes
    Route::middleware(['auth:customer'])->group(function () {
        //Route::get('dashboard', fn() => view('customer.dashboard'))->name('customer.dashboard');
        //Route::get('dashboard', [CustomerAuthController::class, 'index']) ->name('customer.dashboard');
        Route::get('dashboard', [EwalletController::class, 'dashboard'])->name('customer.dashboard');
        Route::get('profile', [CustomerAuthController::class, 'edit'])->name('customer.profile.edit');
        Route::post('profile', [CustomerAuthController::class, 'update'])->name('customer.profile.update');
    });
});
/*
|--------------------------------------------------------------------------
| System User Routes (web guard)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:web'])->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    //Route::get('/', [CustomerAuthController::class, 'showLoginForm'])->name('customer.login');
    Route::match(['get','post'], '/logout', function () {
        Auth::logout();
        return redirect('login');
    })->name('auth.logout');
    //Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/dashboard/export', [DashboardController::class, 'exportPdf'])->name('dashboard.export');
    //});

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('outlets', OutletController::class);

    Route::get('/devices/{device}/qrcode', [DeviceController::class, 'generateQr'])->name('devices.qrcode');
    Route::get('/devices/{device}/qrcode-inline', [DeviceController::class, 'generateQrInline'])->name('devices.qrcode.inline');
    //Route::get('/device/{serial}', [DeviceController::class, 'scan'])->name('device.scan');

    //report
    Route::get('/reports/members', [ReportController::class, 'index'])->name('reports.members.index');
    Route::get('/reports/members/weekly', [ReportController::class, 'weekly'])->name('reports.members.weekly');
    Route::get('/reports/members/monthly', [ReportController::class, 'monthly'])->name('reports.members.monthly');
    Route::get('/reports/maintenance', [ReportController::class, 'deviceMaintenance'])->name('reports.maintenance');
    Route::get('/reports/maintenance/pdf', [ReportController::class, 'exportPdf'])->name('reports.maintenance.pdf');

    //merchant setting
    Route::get('/merchant/setting', [MerchantConfigController::class, 'edit'])->name('admin.merchant.setting');
    Route::patch('/merchant/setting', [MerchantConfigController::class, 'update'])->name('admin.merchant.update');
    Route::get('/merchant/invoice', [MerchantConfigController::class, 'showReceipt'])->name('admin.merchant.invoice');
    
    Route::middleware(['auth', 'can:ewallet.manage'])->prefix('admin')->group(function () {
        // Ewallet adjustments 
        Route::get('/ewallet', [EwalletAdminController::class, 'index'])->name('admin.ewallet.index'); 
        Route::get('/ewallet/{customer}/adjust', [EwalletAdminController::class, 'adjust'])->name('admin.ewallet.adjust');
        Route::get('/ewallet/{customer}/ledger', [EwalletAdminController::class, 'ledger'])->name('admin.ewallet.ledger');
        Route::post('/ewallet/{account}/adjust', [EwalletAdminController::class, 'storeAdjust'])->name('admin.ewallet.storeAdjust');
        Route::get('/ewallet/transaction', [EwalletTransactionAdminController::class, 'index'])->name('admin.ewallet.transaction');

        // Top-up packages CRUD 
        Route::get('/packages', [TopupPackageController::class, 'index'])->name('admin.packages.index'); 
        Route::post('/packages', [TopupPackageController::class, 'store'])->name('admin.packages.store'); 
        Route::put('/packages/{package}', [TopupPackageController::class, 'update'])->name('admin.packages.update'); 
        Route::post('/packages/{package}/toggle', [TopupPackageController::class, 'toggle'])->name('admin.packages.toggle');
        Route::get('/packages/create', [TopupPackageController::class, 'create'])->name('admin.packages.create');
    });
    


    /*
    |--------------------------------------------------------------------------
    | Admin Routes (web guard + role:admin)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin', [UserController::class, 'index'])->name('admin.dashboard');

        // User/Role/Permission management
        Route::resource('/admin/users', UserController::class);
        Route::resource('/admin/roles', RoleController::class);
        Route::resource('/admin/permissions', PermissionController::class);

        // Business entities
        Route::resource('brands', BrandController::class);
        Route::put('brands', [BrandController::class, 'store'])->name('admin.logo.store');
        Route::resource('managers', ManagerController::class);
        Route::resource('suppliers', SupplierController::class);

        // Devices
        Route::resource('/devices', DeviceController::class);
        Route::match(['get', 'post'], '/devices/CSV', [DeviceController::class, 'CSV'])->name('devices.CSV');
        Route::post('/devices/import', [DeviceController::class, 'confirmImport'])->name('devices.import.confirm');
        Route::put('/devices/{log}/rollback', [DeviceController::class, 'rollback'])->name('devices.rollback');
        Route::post('/devices/faulty', [DeviceController::class, 'markFaulty'])->name('devices.markFaulty');

        // Device outlets
        Route::resource('/device_outlets', DeviceOutletController::class);
        Route::match(['get', 'post'], '/device_outlet/CSV', [DeviceOutletController::class, 'CSV'])->name('device_outlets.CSV');
        
        // Payment gateway
        Route::resource('payment_gateway', PaymentGatewaySettingController::class);

        // Customer management (admin side)
        Route::resource('customers', CustomerController::class);
        Route::match(['get', 'post'], 'customers/CSV', [CustomerController::class, 'CSV'])->name('customers.CSV');
        //Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
        Route::post('customers/import', [CustomerController::class, 'confirmImport'])->name('customers.import.confirm');
        Route::get('customers/sample-csv', [CustomerController::class, 'sampleCsv'])->name('customers.sampleCsv');

        // Mail server
        Route::resource('mailserver', MailServerController::class);
        Route::post('mailserver/{mailserver}/test', [MailServerController::class, 'test'])->name('mailserver.test');

        //logo setting
        Route::get('/admin/logo', [LogoController::class, 'edit'])->name('admin.logo.edit');
        Route::put('/admin/logo', [LogoController::class, 'update'])->name('admin.logo.update');
        Route::put('/admin/favicon', [LogoController::class, 'updateFavicon'])->name('admin.favicon.update');

        //device transaction
        Route::get('/admin/device-transactions/{transaction}', [DeviceTransactionAdminController::class, 'show'])->name('admin.device-transactions.show');
        Route::get('/admin/device-transactions', [DeviceTransactionAdminController::class, 'index'])->name('admin.device-transactions.index');
        Route::get('/admin/device-transactions/activate', [DeviceTransactionAdminController::class, 'activate'])->name('admin.device-transactions.activate');
        Route::get('/admin/device-transactions/refund', [DeviceTransactionAdminController::class, 'refund'])->name('admin.device-transactions.refund');

        //payment gateway transaction
        Route::get('/admin/paymentgateway/{transaction}', [PaymentGatewayAdminController::class, 'show'])->name('admin.paymentgateway.show');
        Route::get('/admin/paymentgateway', [PaymentGatewayAdminController::class, 'index'])->name('admin.paymentgateway.index');
        Route::get('/admin/paymentgateway/refund', [PaymentGatewayAdminController::class, 'refund'])->name('admin.paymentgateway.refund');

        //terms of agreement
        Route::get('/terms', [TermsOfServiceController::class, 'index'])->name('admin.terms.index');
        Route::post('/terms', [TermsOfServiceController::class, 'store'])->name('admin.terms.store');
        Route::patch('/terms/{term}', [TermsOfServiceController::class, 'update'])->name('admin.terms.update');

        //backup
        Route::middleware(['auth', 'can:setting.backup'])->group(function () {
            Route::get('/admin/backup', [BackupController::class, 'index'])->name('backup.index');
            Route::get('/admin/backup/history', [BackupController::class, 'history'])->name('backup.history');
            Route::post('/admin/backup/create', [BackupController::class, 'create'])->name('backup.create');
            Route::get('/admin/backup/status/{backupId}', [BackupController::class, 'checkStatus']);
            Route::get('/admin/backup/download/{filename}', [BackupController::class, 'download'])->name('backup.download');
        });
        
        Route::get('/admin/health', \Spatie\Health\Http\Controllers\HealthCheckResultsController::class)->name('admin.health');
        //logs
        Route::get('mailserver/logs', [MailServerController::class, 'logs'])->name('mailserver.logs');
        Route::get('admin/logs', [UserController::class, 'userLogs'])->name('admin.user.logs');
    });
});

require __DIR__.'/auth.php';
