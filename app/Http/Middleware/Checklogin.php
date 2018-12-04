<?php
namespace App\Http\Middleware;
use Closure;
class Checklogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (empty(session("id"))) {
            if ($result == '200') {
                return $next($request);
            } else {
                return response("请登录", 403)->header("X-CSRF-TOKEN", csrf_token());
            }
        } else if (!empty(session("id"))) {
            return $next($request);
        }
    }
}