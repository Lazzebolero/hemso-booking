<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantStatisticsAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('restaurant_statistics_auth')) {
            return redirect()->route('restaurant-statistics.login');
        }

        return $next($request);
    }
}