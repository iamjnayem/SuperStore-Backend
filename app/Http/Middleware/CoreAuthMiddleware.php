<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CoreAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $secret = $request->secret || $request->header('secret');

        if($secret != 'jnddBjJm6ZV7X9gx'){
            return response()->json([
                'data' => null,
                'messages' => ['Invalid secret'],
                'code' => 401,
            ],401);
        }

        return $next($request);
    }
}
