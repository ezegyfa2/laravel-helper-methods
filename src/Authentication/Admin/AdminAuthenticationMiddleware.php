<?php
namespace Ezegyfa\LaravelHelperMethods\Authentication\Admin;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuthenticationMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('admin')->user()) {
            return $next($request);
        }
        else if ($request->ajax() || $request->wantsJson()) {
            return response(['error' => __('auth.unauthorized')], 401);
        }
        else {
            return redirect(route('admin.login'));
        }
    }
}