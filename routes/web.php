
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\SipongiController;

Route::get('/', function () {
    return view('dashboard');
});

Route::get('/api/sipongi-hotspots', [
    SipongiController::class,
    'hotspots'
]);

Route::get('/api/sensor/latest', [
    SensorController::class,
    'latest',
]);
