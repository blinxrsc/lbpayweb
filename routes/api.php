<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceStatusController;

Route::post('/device/status', [DeviceStatusController::class, 'updateStatus'])
    ->middleware('device.key');

Route::post('/device/status/bulk', [DeviceStatusController::class, 'bulkUpdate'])
    ->middleware('device.key');