<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceStatusController;
use App\Http\Controllers\Api\TechnicianAuthController;
use App\Http\Controllers\Api\TechnicianDeviceController;

Route::post('/device/status', [DeviceStatusController::class, 'updateStatus'])
    ->middleware('device.key');

Route::post('/device/status/bulk', [DeviceStatusController::class, 'bulkUpdate'])
    ->middleware('device.key');

// --- Flutter technician app ---
Route::post('/technician/login', [TechnicianAuthController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('technician')->group(function () {
    Route::post('/logout', [TechnicianAuthController::class, 'logout']);
    Route::get('/devices/{serial}', [TechnicianDeviceController::class, 'show']);
    Route::put('/devices/{serial}/parameters', [TechnicianDeviceController::class, 'updateParameters']);
    Route::post('/devices/{serial}/start', [TechnicianDeviceController::class, 'start']);
});