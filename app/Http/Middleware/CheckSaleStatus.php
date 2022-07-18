<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Lead;

class CheckSaleStatus
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
        $excludeApis = ['order/confirmation'];
        $currentUri = $request->segment(2).'/'.$request->segment(3);
        
        if ($request->filled('visit_id') && !in_array($currentUri, $excludeApis)) {
            $leadId = decryptGdprData($request->visit_id);
            if (Lead::isSaleCreated($leadId)) { 
                return successResponse('Sale is already created for this visitor', SALE_CREATED_CODE);
            }
        }
        return $next($request);
    }
}
