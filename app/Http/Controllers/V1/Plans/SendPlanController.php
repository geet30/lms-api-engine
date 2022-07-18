<?php

namespace App\Http\Controllers\V1\Plans;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\SendPlanRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\Energy\{EnergySendPlans};
use App\Models\Broadband\{BroadbandSendPlans};

class SendPlanController extends Controller
{
    protected $status = HTTP_STATUS_OK;
    protected $message = HTTP_SUCCESS;
    protected $response = null;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
    //send-plan function
    public function postSendPlan(Request $request)
    {
        try {

            $request = app('request');
            $data = [];
            $reqObj = new SendPlanRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
           
            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            }

            $data = [];

            $serviceId = $request->header('ServiceId');
            /* $reqObj = new SendPlanRequest($request);

            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, PLANLIST_VALIDATION_CODE);
            } else { */
               
            $data = EnergySendPlans::getSendPlanData($request,$serviceId);
              
            if ($data) {
                return errorResponse($data,HTTP_STATUS_SERVER_ERROR, PLANLIST_ERROR_CODE);
            } else {
                return successResponse('Mail have been sent',$data, 2001);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PLANLIST_ERROR_CODE, __FUNCTION__);
        }
    }
}
