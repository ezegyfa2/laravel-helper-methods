<?php

namespace Ezegyfa\LaravelHelperMethods;

use Closure;

class HttpsRedirecter {

    public function handle($request, Closure $next)
    {
        if (!$request->secure() && app()->environment('production')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request); 
    }
}
