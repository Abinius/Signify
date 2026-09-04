<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * 管理员权限中间件
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->is_admin) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthorized'], 403)
                : redirect('/')->with('error', '您没有管理员权限');
        }

        return $next($request);
    }
}
