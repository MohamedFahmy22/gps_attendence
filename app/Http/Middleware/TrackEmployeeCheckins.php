<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrackEmployeeCheckins
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $userId = auth()->id();

        if (Cache::has('request_' . $userId)) {
            return response()->json(['message' => 'You have already made a request today'], 429);
        }

        Cache::put('request_' . $userId, true, now()->endOfDay());

        return $next($request);

    }
}
