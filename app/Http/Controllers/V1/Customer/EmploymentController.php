<?php

namespace App\Http\Controllers\V1\Customer;

use Illuminate\Http\Request;
use App\Models\LeadEmploymentDetails;


class EmploymentController
{
    public function saveEmploymentDetails(Request $request)
    {
        try {
            return LeadEmploymentDetails::saveEmploymentDetails($request);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ABN_ERROR_CODE, __FUNCTION__);
        }
    }
}
