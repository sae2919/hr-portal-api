<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RouteListController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api-routes', [RouteListController::class, 'index']);

