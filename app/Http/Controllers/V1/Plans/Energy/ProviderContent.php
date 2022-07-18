<?php

namespace App\Http\Controllers\V1\Plans\Energy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\{ApplyNowContentRequest,ProviderManageRequest};
use Illuminate\Support\Facades\Validator;
use App\Models\{PlanMobile};
class ProviderContent extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    /**
     * Author: harvinder (13-may-2022)
     * get provider move in 
     */
  

    public function providerMoveInContent(Request $request){

        try{
            $response = PlanMobile::getProviderMoveIn($request);
           
            if($response['status'] == true){
                return successResponse("Move-In content ",2001,$response);
            }else{
                return errorResponse( $response['message'],HTTP_STATUS_NOT_FOUND,HTTP_DATA_NOT_FOUND);
            }
           
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(),HTTP_STATUS_SERVER_ERROR,PROVIDERSECTION_ERROR_CODE, __FUNCTION__);
        }
    }
   
    

    
}

