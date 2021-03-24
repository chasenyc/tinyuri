<?php

use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    $urlId = session()->get('urlId', null);
    return view('urls.create', ['urlId' => $urlId]);
})->name('home');

Route::post('/url', function(Request $request) {
    $url = Url::create([
        'url' => $request->input('url')
    ]);

    return redirect(route('home'))->with(['urlId' => $url->id]);
    // return $url->id;
})->name('create');

Route::get('/url/{url}', function (Url $url) {
    return redirect($url->url);
})->name('shortened');