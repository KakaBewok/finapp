<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('app_locale')) {
            app()->setLocale(session()->get('app_locale'));
        } else {
            app()->setLocale(config('app.locale'));
        }
        return $next($request);
    }
}
