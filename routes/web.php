<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\ContactController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [ContactController::class, 'index']);

Route::get('/sync-contacts', function () {
    echo Artisan::call('hotspot:syncContacts');
});
