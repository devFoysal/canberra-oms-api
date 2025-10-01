<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\V1\{
    AuthController,
    UserController,
    CustomerController,
    CategoryController,
    ProductController,
    OrderController,
    SalesRepresentativeController,
    InvoiceController,
    PaymentController,
    ShippingController,
};

// API Version 1 routes
Route::prefix('v1')->group(function () {

    // Public routes
    Route::prefix('auth')->group(function () {
        Route::post('sign-up', [AuthController::class, 'signUp']);
        Route::post('sign-in', [AuthController::class, 'signIn']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
        Route::prefix('admin')->group(function () {
            Route::post('sign-in', [AuthController::class, 'adminSignIn']);
        });
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {

        Route::prefix('auth')->group(function () {
            Route::post('sign-out', [AuthController::class, 'signOut']);
        });

        // User routes
        Route::prefix('users')->group(function () {
            Route::post('/', [UserController::class, 'store']);
            Route::get('/', [UserController::class, 'index']);
            Route::get('/me', [UserController::class, 'getMe']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::post('/{id}', [UserController::class, 'update']);
            Route::delete('/{id}', [UserController::class, 'destroy']);
        });

        // SalesRepresentative routes
        Route::prefix('sales-representatives')->group(function () {
            Route::post('/', [SalesRepresentativeController::class, 'store']);
            Route::get('/', [SalesRepresentativeController::class, 'index']);
            Route::get('/{id}', [SalesRepresentativeController::class, 'show']);
            Route::post('/{id}', [SalesRepresentativeController::class, 'update']);
            Route::delete('/{id}', [SalesRepresentativeController::class, 'destroy']);

            Route::prefix('my')->group(function () {
                // orders
                Route::get('/recent-orders', [SalesRepresentativeController::class, 'getMyRecentOrders']);
                Route::get('/orders', [SalesRepresentativeController::class, 'getMyOrders']);
            });
        });

         // Customer routes
        Route::prefix('customers')->group(function () {
            Route::post('/', [CustomerController::class, 'store']);
            Route::get('/', [CustomerController::class, 'index']);
            Route::get('/{id}', [CustomerController::class, 'show']);
            Route::post('/{id}', [CustomerController::class, 'update']);
            Route::delete('/{id}', [CustomerController::class, 'destroy']);
        });

        // Category routes
        Route::prefix('categories')->group(function () {
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/{id}', [CategoryController::class, 'show']);
            Route::post('/{id}', [CategoryController::class, 'update']);
            Route::delete('/{id}', [CategoryController::class, 'destroy']);
        });

        // Product routes
        Route::prefix('products')->group(function () {
            Route::post('/', [ProductController::class, 'store']);
            Route::get('/', [ProductController::class, 'index']);
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::post('/{id}', [ProductController::class, 'update']);
            Route::delete('/{id}', [ProductController::class, 'destroy']);
        });

        // Order routes
        Route::prefix('orders')->group(function () {
            Route::post('/', [OrderController::class, 'store']);
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/{id}', [OrderController::class, 'show']);
            Route::post('/{id}', [OrderController::class, 'update']);
            Route::post('/{id}/add-more-item', [OrderController::class, 'addMoreOrderItem']);
            Route::post('/{id}/remove-item', [OrderController::class, 'removeOrderItem']);
            Route::delete('/{id}', [OrderController::class, 'destroy']);
        });

        // Invoice routes
        Route::prefix('invoices')->group(function () {
            Route::post('/', [InvoiceController::class, 'store']);
            Route::get('/', [InvoiceController::class, 'index']);
            Route::get('/{id}', [InvoiceController::class, 'show']);
            Route::get('/{id}/download', [InvoiceController::class, 'downloadInvoice']);
            Route::post('/{id}', [InvoiceController::class, 'update']);
            Route::delete('/{id}', [InvoiceController::class, 'destroy']);
        });

        // Payment routes
        Route::prefix('payments')->group(function () {
            Route::post('/', [PaymentController::class, 'store']);
            Route::get('/', [PaymentController::class, 'index']);
            // Route::get('/{id}', [PaymentController::class, 'show']);
            // Route::post('/{id}', [PaymentController::class, 'update']);
            // Route::delete('/{id}', [PaymentController::class, 'destroy']);
        });

         // Shipping routes
        Route::prefix('shippings')->group(function () {
            Route::post('/ready_to_ship/{id}', [ShippingController::class, 'readyToShip']);
            Route::post('/shipped/{id}', [ShippingController::class, 'store']);
            Route::post('/delivered/{id}', [ShippingController::class, 'delivered']);
            Route::get('/', [ShippingController::class, 'index']);
            // Route::get('/{id}', [PaymentController::class, 'show']);
            // Route::post('/{id}', [PaymentController::class, 'update']);
            // Route::delete('/{id}', [PaymentController::class, 'destroy']);
        });

        // Add more protected routes here
    });
});
