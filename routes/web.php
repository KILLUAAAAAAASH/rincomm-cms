<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CashPaymentController;
use App\Http\Controllers\Admin\EquipmentAssignmentController;
use App\Http\Controllers\Admin\EquipmentRetrievalController;
use App\Http\Controllers\Admin\EquipmentAssetController;
use App\Http\Controllers\Admin\CustomerDocumentController;
use App\Http\Controllers\Admin\HeroSlideController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\JobOrderController as AdminJobOrderController;
use App\Http\Controllers\Admin\PlanChangeRequestController;
use App\Http\Controllers\Admin\RelocationRequestController;
use App\Http\Controllers\Admin\ServiceApplicationController as AdminServiceApplicationController;
use App\Http\Controllers\Admin\ServicePlanController;
use App\Http\Controllers\Admin\SubscriberController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmployeeActivationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\PasswordResetVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegistrationVerificationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Customer\BillingController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\GatewayPaymentController;
use App\Http\Controllers\Customer\TicketController as CustomerTicketController;
use App\Http\Controllers\Customer\PlanChangeController as CustomerPlanChangeController;
use App\Http\Controllers\PaymentReceiptController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceApplicationController;
use App\Http\Controllers\ServiceCoverageController;
use App\Http\Controllers\Technician\DashboardController as TechnicianDashboardController;
use App\Http\Controllers\Technician\JobOrderController;
use App\Http\Controllers\Technician\JobOrderProofController;
use App\Http\Controllers\Webhook\PayMongoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [LandingPageController::class, 'index'])
    ->name('home');

Route::get('/apply/coverage', [ServiceCoverageController::class, 'create'])
    ->name('apply.coverage');

Route::post('/apply/coverage', [ServiceCoverageController::class, 'check'])
    ->name('apply.coverage.check');

Route::post(
    '/webhooks/paymongo',
    PayMongoWebhookController::class
)->name('webhooks.paymongo');

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])
        ->name('password.request');

    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::get(
        '/forgot-password/verify/{challenge}',
        [PasswordResetVerificationController::class, 'create']
    )->name('password.reset.verify');

    Route::post(
        '/forgot-password/verify/{challenge}',
        [PasswordResetVerificationController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('password.reset.verify.store');

    Route::post(
        '/forgot-password/verify/{challenge}/resend',
        [PasswordResetVerificationController::class, 'resend']
    )
        ->middleware('throttle:3,1')
        ->name('password.reset.verify.resend');

    Route::get(
        '/reset-password/{challenge}',
        [ResetPasswordController::class, 'create']
    )->name('password.reset');

    Route::post(
        '/reset-password/{challenge}',
        [ResetPasswordController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('password.update');

    Route::get('/register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('register.store');

    Route::get(
        '/register/verify/{challenge}',
        [RegistrationVerificationController::class, 'create']
    )->name('register.verify');

    Route::post(
        '/register/verify/{challenge}',
        [RegistrationVerificationController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('register.verify.store');

    Route::post(
        '/register/verify/{challenge}/resend',
        [RegistrationVerificationController::class, 'resend']
    )
        ->middleware('throttle:3,1')
        ->name('register.verify.resend');

    Route::post(
        '/register/verify/{challenge}/cancel',
        [RegistrationVerificationController::class, 'cancel']
    )
        ->middleware('throttle:3,1')
        ->name('register.verify.cancel');

    Route::get(
        '/employee/activate/channel',
        [EmployeeActivationController::class, 'channel']
    )->name('employee.activation.channel');

    Route::post(
        '/employee/activate/channel',
        [EmployeeActivationController::class, 'send']
    )
        ->middleware('throttle:3,1')
        ->name('employee.activation.channel.store');

    Route::get(
        '/employee/activate/{challenge}',
        [EmployeeActivationController::class, 'create']
    )->name('employee.activation');

    Route::post(
        '/employee/activate/{challenge}',
        [EmployeeActivationController::class, 'store']
    )
        ->middleware('throttle:5,1')
        ->name('employee.activation.store');

    Route::post(
        '/employee/activate/{challenge}/resend',
        [EmployeeActivationController::class, 'resend']
    )
        ->middleware('throttle:3,1')
        ->name('employee.activation.resend');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Administrator / Staff Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'role:admin,staff'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Job Orders
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Support Tickets
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/tickets', [AdminTicketController::class, 'index'])
        ->name('admin.tickets.index');

    Route::get('/admin/tickets/create', [AdminTicketController::class, 'create'])
        ->name('admin.tickets.create');

    Route::post('/admin/tickets', [AdminTicketController::class, 'store'])
        ->name('admin.tickets.store');

    Route::get('/admin/tickets/{ticket}', [AdminTicketController::class, 'show'])
        ->name('admin.tickets.show');
    Route::patch(
        '/admin/tickets/{ticket}/assignment',
        [\App\Http\Controllers\Admin\TicketWorkflowController::class, 'assign']
    )->name('admin.tickets.assignment.update');

    Route::patch(
        '/admin/tickets/{ticket}/status',
        [\App\Http\Controllers\Admin\TicketWorkflowController::class, 'transition']
    )->name('admin.tickets.status.update');
    // Module 6 Feature 3: Customer-visible replies and private notes.
    Route::post(
        '/admin/tickets/{ticket}/replies',
        [\App\Http\Controllers\Admin\TicketConversationController::class, 'reply']
    )->name('admin.tickets.replies.store');

    Route::post(
        '/admin/tickets/{ticket}/notes',
        [\App\Http\Controllers\Admin\TicketConversationController::class, 'addNote']
    )->name('admin.tickets.notes.store');

    Route::get('/admin/job-orders', [AdminJobOrderController::class, 'index'])
        ->name('admin.job-orders.index');

    Route::get('/admin/job-orders/create', [AdminJobOrderController::class, 'create'])
        ->name('admin.job-orders.create');

    Route::post('/admin/job-orders', [AdminJobOrderController::class, 'store'])
        ->name('admin.job-orders.store');

    Route::patch(
        '/admin/job-orders/{jobOrder}/assignment',
        [AdminJobOrderController::class, 'updateAssignment']
    )->name('admin.job-orders.assignment.update');

    Route::get('/admin/job-orders/{jobOrder}', [AdminJobOrderController::class, 'show'])
        ->name('admin.job-orders.show');

    /*
    |--------------------------------------------------------------------------
    | Equipment & Network Inventory — Module 8 Feature 1
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Module 8, Feature 2: Subscriber equipment binding
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Module 8, Feature 3: Physical equipment retrieval logging
    |--------------------------------------------------------------------------
    */
    Route::get('/admin/equipment-retrievals', [EquipmentRetrievalController::class, 'index'])
        ->name('admin.equipment-retrievals.index');

    Route::get('/admin/equipment-retrievals/create', [EquipmentRetrievalController::class, 'create'])
        ->name('admin.equipment-retrievals.create');

    Route::post('/admin/equipment-retrievals', [EquipmentRetrievalController::class, 'store'])
        ->name('admin.equipment-retrievals.store');

    Route::get('/admin/equipment-retrievals/{equipmentRetrieval}', [EquipmentRetrievalController::class, 'show'])
        ->name('admin.equipment-retrievals.show');

    Route::get('/admin/equipment-assignments', [EquipmentAssignmentController::class, 'index'])
        ->name('admin.equipment-assignments.index');

    Route::get('/admin/equipment-assignments/create', [EquipmentAssignmentController::class, 'create'])
        ->name('admin.equipment-assignments.create');

    Route::post('/admin/equipment-assignments', [EquipmentAssignmentController::class, 'store'])
        ->name('admin.equipment-assignments.store');

    Route::get('/admin/equipment-assets', [EquipmentAssetController::class, 'index'])
        ->name('admin.equipment-assets.index');

    Route::get('/admin/equipment-assets/create', [EquipmentAssetController::class, 'create'])
        ->name('admin.equipment-assets.create');

    Route::post('/admin/equipment-assets', [EquipmentAssetController::class, 'store'])
        ->name('admin.equipment-assets.store');

    Route::get('/admin/equipment-assets/{equipmentAsset}/edit', [EquipmentAssetController::class, 'edit'])
        ->name('admin.equipment-assets.edit');

    Route::patch('/admin/equipment-assets/{equipmentAsset}', [EquipmentAssetController::class, 'update'])
        ->name('admin.equipment-assets.update');

    Route::get('/admin/equipment-assets/{equipmentAsset}', [EquipmentAssetController::class, 'show'])
        ->name('admin.equipment-assets.show');

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/users', [UserController::class, 'index'])
        ->name('admin.users.index');

    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/users/create', [UserController::class, 'create'])
            ->name('admin.users.create');

        Route::post('/admin/users', [UserController::class, 'store'])
            ->name('admin.users.store');

        Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])
            ->name('admin.activity-logs.index');
    });

    Route::patch('/admin/users/{user}/status', [UserController::class, 'updateStatus'])
        ->name('admin.users.status');

    /*
    |--------------------------------------------------------------------------
    | Subscribers
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/subscribers', [SubscriberController::class, 'index'])
        ->name('admin.subscribers.index');

    Route::get('/admin/subscribers/{subscriber}/edit', [SubscriberController::class, 'edit'])
        ->name('admin.subscribers.edit');

    Route::patch('/admin/subscribers/{subscriber}', [SubscriberController::class, 'update'])
        ->name('admin.subscribers.update');

    Route::get('/admin/subscribers/{subscriber}', [SubscriberController::class, 'show'])
        ->name('admin.subscribers.show');

    Route::patch('/admin/subscribers/{subscriber}/status', [SubscriberController::class, 'updateStatus'])
        ->name('admin.subscribers.status');

    Route::patch(
        '/admin/subscribers/{subscriber}/subscriptions/{subscription}/activate',
        [SubscriptionController::class, 'activate']
    )->name('admin.subscribers.subscriptions.activate');

    Route::patch(
        '/admin/subscribers/{subscriber}/subscriptions/{subscription}/discount',
        [SubscriptionController::class, 'updateDiscount']
    )->name('admin.subscribers.subscriptions.discount');

    /*
    |--------------------------------------------------------------------------
    | Customer Documents
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/admin/subscribers/{subscriber}/documents',
        [CustomerDocumentController::class, 'store']
    )->name('admin.subscribers.documents.store');

    Route::get(
        '/admin/subscribers/{subscriber}/documents/{document}',
        [CustomerDocumentController::class, 'show']
    )->name('admin.subscribers.documents.show');

    Route::get(
        '/admin/subscribers/{subscriber}/documents/{document}/download',
        [CustomerDocumentController::class, 'download']
    )->name('admin.subscribers.documents.download');

    Route::delete(
        '/admin/subscribers/{subscriber}/documents/{document}',
        [CustomerDocumentController::class, 'destroy']
    )->name('admin.subscribers.documents.destroy');

    /*
    |--------------------------------------------------------------------------
    | Plan Change Requests
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/admin/subscribers/{subscriber}/plan-change-requests',
        [PlanChangeRequestController::class, 'store']
    )->name('admin.subscribers.plan-change-requests.store');

    Route::patch(
        '/admin/subscribers/{subscriber}/plan-change-requests/{planChangeRequest}/approve',
        [PlanChangeRequestController::class, 'approve']
    )->name('admin.subscribers.plan-change-requests.approve');

    Route::patch(
        '/admin/subscribers/{subscriber}/plan-change-requests/{planChangeRequest}/reject',
        [PlanChangeRequestController::class, 'reject']
    )->name('admin.subscribers.plan-change-requests.reject');

    /*
    |--------------------------------------------------------------------------
    | Relocation Requests
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/admin/subscribers/{subscriber}/relocation-requests',
        [RelocationRequestController::class, 'store']
    )->name('admin.subscribers.relocation-requests.store');

    Route::patch(
        '/admin/subscribers/{subscriber}/relocation-requests/{relocationRequest}/approve',
        [RelocationRequestController::class, 'approve']
    )->name('admin.subscribers.relocation-requests.approve');

    Route::patch(
        '/admin/subscribers/{subscriber}/relocation-requests/{relocationRequest}/reject',
        [RelocationRequestController::class, 'reject']
    )->name('admin.subscribers.relocation-requests.reject');

    Route::patch(
        '/admin/subscribers/{subscriber}/relocation-requests/{relocationRequest}/complete',
        [RelocationRequestController::class, 'complete']
    )->name('admin.subscribers.relocation-requests.complete');

    /*
    |--------------------------------------------------------------------------
    | Service Applications
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/applications', [AdminServiceApplicationController::class, 'index'])
        ->name('admin.applications.index');

    Route::get('/admin/applications/{application}', [AdminServiceApplicationController::class, 'show'])
        ->name('admin.applications.show');

    Route::post('/admin/applications/{application}/approve', [AdminServiceApplicationController::class, 'approve'])
        ->name('admin.applications.approve');

    Route::post('/admin/applications/{application}/reject', [AdminServiceApplicationController::class, 'reject'])
        ->name('admin.applications.reject');

    /*
    |--------------------------------------------------------------------------
    | Service Plans
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/service-plans', [ServicePlanController::class, 'index'])
        ->name('admin.service-plans.index');

    Route::get('/admin/service-plans/create', [ServicePlanController::class, 'create'])
        ->name('admin.service-plans.create');

    Route::post('/admin/service-plans', [ServicePlanController::class, 'store'])
        ->name('admin.service-plans.store');

    Route::get('/admin/service-plans/{servicePlan}/edit', [ServicePlanController::class, 'edit'])
        ->name('admin.service-plans.edit');

    Route::patch('/admin/service-plans/{servicePlan}', [ServicePlanController::class, 'update'])
        ->name('admin.service-plans.update');

    Route::patch('/admin/service-plans/{servicePlan}/status', [ServicePlanController::class, 'updateStatus'])
        ->name('admin.service-plans.status');

    /*
    |--------------------------------------------------------------------------
    | Billing & Invoicing
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/invoices', [InvoiceController::class, 'index'])
        ->name('admin.invoices.index');

    Route::post(
        '/admin/invoices/{invoice}/cash-payments',
        [CashPaymentController::class, 'store']
    )->name('admin.invoices.cash-payments.store');

    Route::get('/admin/invoices/{invoice}', [InvoiceController::class, 'show'])
        ->name('admin.invoices.show');

    Route::get(
        '/admin/invoices/{invoice}/payments/{payment}/receipt',
        [PaymentReceiptController::class, 'show']
    )->name('admin.invoices.payments.receipt.show');

    /*
    |--------------------------------------------------------------------------
    | Hero Slides
    |--------------------------------------------------------------------------
    */

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('hero-slides', HeroSlideController::class)
            ->except('show');
    });
});

/*
|--------------------------------------------------------------------------
| Technician Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'role:technician'])->group(function () {
    Route::get('/technician/dashboard', [TechnicianDashboardController::class, 'index'])
        ->name('technician.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Job Orders
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/technician/job-orders',
        [JobOrderController::class, 'index']
    )->name('technician.job-orders.index');

    Route::get(
        '/technician/job-orders/{jobOrder}',
        [JobOrderController::class, 'show']
    )->name('technician.job-orders.show');

    Route::patch(
        '/technician/job-orders/{jobOrder}/start',
        [JobOrderController::class, 'start']
    )->name('technician.job-orders.start');

    Route::patch(
        '/technician/job-orders/{jobOrder}/complete',
        [JobOrderController::class, 'complete']
    )->name('technician.job-orders.complete');

    /*
    |--------------------------------------------------------------------------
    | Job Order Proofs
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/technician/job-orders/{jobOrder}/proofs',
        [JobOrderProofController::class, 'store']
    )->name('technician.job-orders.proofs.store');

    Route::get(
        '/technician/job-orders/{jobOrder}/proofs/{proof}',
        [JobOrderProofController::class, 'show']
    )->name('technician.job-orders.proofs.show');

    Route::get(
        '/technician/job-orders/{jobOrder}/proofs/{proof}/download',
        [JobOrderProofController::class, 'download']
    )->name('technician.job-orders.proofs.download');

    Route::delete(
        '/technician/job-orders/{jobOrder}/proofs/{proof}',
        [JobOrderProofController::class, 'destroy']
    )->name('technician.job-orders.proofs.destroy');
});

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])
        ->name('customer.dashboard');

    // Customer-only plan management and requests. No subscriber ID in the URL.
    /*
    |--------------------------------------------------------------------------
    | Customer Support Tickets
    |--------------------------------------------------------------------------
    */

    Route::get('/customer/tickets', [CustomerTicketController::class, 'index'])
        ->name('customer.tickets.index');

    Route::get('/customer/tickets/create', [CustomerTicketController::class, 'create'])
        ->name('customer.tickets.create');

    Route::post('/customer/tickets', [CustomerTicketController::class, 'store'])
        ->name('customer.tickets.store');

    Route::get('/customer/tickets/{ticket}', [CustomerTicketController::class, 'show'])
        ->name('customer.tickets.show');
    // Module 6 Feature 3: Subscriber ticket replies.
    Route::post(
        '/customer/tickets/{ticket}/replies',
        [\App\Http\Controllers\Customer\TicketConversationController::class, 'reply']
    )->name('customer.tickets.replies.store');

    Route::get('/customer/plan-changes', [CustomerPlanChangeController::class, 'index'])
        ->name('customer.plan-changes.index');

    Route::post('/customer/plan-changes', [CustomerPlanChangeController::class, 'store'])
        ->name('customer.plan-changes.store');

    Route::get('/customer/billing', [BillingController::class, 'index'])
        ->name('customer.billing.index');

    Route::get(
        '/customer/billing/invoices/{invoice}',
        [BillingController::class, 'show']
    )->name('customer.billing.show');

    Route::get(
        '/customer/billing/invoices/{invoice}/payments/{payment}/receipt',
        [PaymentReceiptController::class, 'show']
    )->name('customer.billing.payments.receipt.show');

    Route::post(
        '/customer/billing/invoices/{invoice}/gateway-payments',
        [GatewayPaymentController::class, 'store']
    )->name('customer.billing.gateway-payments.store');

    Route::post(
        '/customer/billing/invoices/{invoice}/gateway-payments/recover',
        [GatewayPaymentController::class, 'recover']
    )->name('customer.billing.gateway-payments.recover');

    Route::get('/customer/application', [ServiceApplicationController::class, 'create'])
        ->name('customer.application.create');

    Route::post('/customer/application', [ServiceApplicationController::class, 'store'])
        ->name('customer.application.store');
});
