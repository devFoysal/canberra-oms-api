<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\V1\{
    InvoiceController,
};

Route::get('/', function () {
    return view('welcome');
});


Route::get('/login', function () {
    return bcrypt('@mrtraders');
    // return response()->json(['message' => 'Login route not available'], 401);
})->name('login');


Route::prefix('invoices')->group(function () {
    Route::get('/{id}/download', [InvoiceController::class, 'downloadInvoice']);
});
