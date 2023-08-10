<?php
namespace Ezegyfa\LaravelHelperMethods\Authentication\Admin;
 
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

 
class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('admin')->user()) {
            return $next($request);
        }
        if ($request->ajax() || $request->wantsJson()) {
            return response(['error' => 'Unauthorized.'], 401);
        } else {
            return redirect(route('admin.login'));
        }
    }
}