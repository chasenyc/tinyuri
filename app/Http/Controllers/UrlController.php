<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Http\Requests\StoreUrlRequest;

class UrlController extends Controller
{
    public function store(StoreUrlRequest $request)
    {
        $url = Url::create([
            'url' => $request->input('url')
        ]);
    
        return redirect(route('home'))->with(['urlId' => $url->base62id()]);
    }
}
