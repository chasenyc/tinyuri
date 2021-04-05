<?php

namespace App\Http\Middleware;

use Auth, Closure;
use Illuminate\Http\Request;

class EnsureNotAuthed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()) {
            return redirect(route('home'));
        }
        
        return $next($request);
    }
}
