<?php

namespace App\Http\Controllers;

use Auth;

class UserUrlsController extends Controller
{
    public function index()
    {
        return view('user.urls-index', ['urls' => Auth::user()->urls]);
    }
}
