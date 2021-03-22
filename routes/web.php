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
    return view('welcome');
});

Route::post('/url', function(Request $request) {
    $url = Url::create([
        'url' => $request->input('url')
    ]);

    return $url->id;
});

Route::get('/url/{url}', function (Url $url) {
    return redirect($url->url);
});