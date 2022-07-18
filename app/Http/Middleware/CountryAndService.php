<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Country;

class CountryAndService
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $countryId = $request->header('CountryId');
        $collectErrors = [];
        
        if (!Country::isExist($countryId)) {
            array_push($collectErrors, 'Country id is required or not valid');
        }

        if (!empty($collectErrors)) {
            return errorResponse($collectErrors, HTTP_STATUS_VALIDATION_ERROR, 2002);
        }

        return $next($request);
    }
}
