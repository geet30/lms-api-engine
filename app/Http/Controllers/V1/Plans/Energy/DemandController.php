<?php

namespace App\Http\Controllers\V1\Plans\Energy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Energy\{DemandRequest};
use App\Models\{MasterTariff,Setting};

class DemandController extends Controller
{
   

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    
    public function __construct()
    {
        
    }
       /**
     * Author: Geetanjali(20-April-2022)
     * getDemandTariff
     */
    public function getDemandTariff(Request $request){
        try {
            $data = [];
            $reqObj = new DemandRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, DEMAND_VALIDATION_CODE);
            } else {
            
                $data = MasterTariff::getDemandTariff($request->distributor_id,$request->property_type);
                if ($data) {
                    return successResponse('Demand Tariff found successfully', DEMAND_SUCCESS_CODE, $data);
                }
                return successResponse('Demand Tariff not found', DEMAND_SUCCESS_CODE, $data);
                // return errorResponse('Demand Tariff not found', HTTP_STATUS_NOT_FOUND, DEMAND_SUCCESS_CODE);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, DEMAND_ERROR_CODE, __FUNCTION__);
        }
        
    }

}
