<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($auth = $request->header('Authorization', '')) {
            $auth = @explode(' ', $auth)[1];
            
            if (password_verify($auth, env('BASIC_AUTH_CRED', ''))) {
                return $next($request);
            }
        }
        return response(null, 401, [
            'WWW-Authenticate' => 'Basic realm=sadiqserver'
        ]);
    }
}
