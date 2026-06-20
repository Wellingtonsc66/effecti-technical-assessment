<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::apiResource('clients', ClientController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('contracts', ContractController::class);

Route::post('contracts/{contract}/items', [ContractController::class, 'addItem']);
Route::delete('contracts/{contract}/items/{contractItem}', [ContractController::class, 'removeItem']);
