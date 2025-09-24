<?php

use App\Http\Controllers\Api\ImportController;
use App\Http\Middleware\CheckApiToken;
use Illuminate\Support\Facades\Route;

Route::middleware([CheckApiToken::class])->group(function () {

    Route::post('imports', [ImportController::class, 'store']);

});

