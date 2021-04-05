<?php

use App\Models\Url;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UrlController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $urlId = session()->get('urlId');
    return view('urls.create', ['urlId' => $urlId]);
})->name('home');

Route::middleware(['unauthed'])->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register.create');
    Route::post('/register', [RegisterController::class, 'store'])->name('register');
    Route::get('/login', [LoginController::class, 'create'])->name('session.create');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::post('/url', [UrlController::class, 'store'])->name('create');

Route::get('/url/{url}', function (Url $url) {
    return redirect($url->url);
})->name('shortened');

