<?php

use App\Modules\Sales\Http\Controllers\PublicReceiptController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')
    ->get('/receipt/{token}', PublicReceiptController::class)
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('sales.receipt.public');
