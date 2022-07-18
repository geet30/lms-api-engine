<?php

namespace App\Http\Controllers\V1\Plans\Energy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\{EnergyPlanRate};

class GeneralController extends Controller
{
    /**
     * Get plan bpid.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function planBPIDdata(Request $request)
    {
        try {
            $bpidData = EnergyPlanRate::getPlanBPIDdata($request);

            if ($bpidData && !$bpidData->isEmpty()) {
                return successResponse('BPID data found successfully', BPID_SUCCESS_CODE, $bpidData);
            }

            return successResponse('BPID not found', BPID_SUCCESS_CODE);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ADDRESS_ERROR_CODE, __FUNCTION__);
        }
    }
}