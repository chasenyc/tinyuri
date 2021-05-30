<?php

namespace App\Http\Controllers;

use Auth;
use App\Models\Url;
use App\Http\Requests\StoreUrlRequest;

class UrlController extends Controller
{
    public function store(StoreUrlRequest $request)
    {
        $url = Url::make([
            'url' => $request->input('url'),
        ]);

        $url->user()->associate(Auth::user());
        $url->save();
    
        return redirect(route('home'))->with(['urlId' => $url->base62id()]);
    }
}
