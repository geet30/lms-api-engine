<?php

namespace App\Http\Controllers\V1\Plans\Mobile;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\{PlanListRequest, PlanInfoRequest, MobileCommonRequest};
use App\Models\{PlanMobile, ConnectionType, Brand, LeadJourneyDataMobile};
use Illuminate\Support\Facades\Validator;

class MobilePlanController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $status = HTTP_STATUS_OK;
    protected $message = HTTP_SUCCESS;
    protected $response = null;

    public function __construct()
    {
        $this->mobileLead = new LeadJourneyDataMobile();
    }
    /**
     * Author: Geetanjali(10-March-2022)
     * get mobile plan listing
     */
    public function planListing(Request $request)
    {

        try {
            $data = [];
            $reqObj = new PlanListRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            $journey_save = true;

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, PLANLIST_VALIDATION_CODE);
            } else {

                // $request_data =  $this->mobileLead->getMobileLeadData($leadId);
                if (isset($request->filter) && $request->filter == 1) {
                    $saveMobileJourneyData = $this->mobileLead->saveMobileJourneyData($request);
                    // if(!$saveMobileJourneyData){
                    //     $journey_save = false;
                    // }  
                }

                // echo "<pre>";print_r($request_data);die;

                // if($journey_save == true){
                $data = PlanMobile::getPlanList($request);
                // }              
                   

                if (isset($data['plan_result'])) {
                    return successResponse('Plan found successfully', PLANLIST_SUCCESS_CODE, $data);
                }
                
                return errorResponse($data, HTTP_STATUS_NOT_FOUND, PLANLIST_ERROR_CODE);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(). $e->getLine(), HTTP_STATUS_SERVER_ERROR, PLANLIST_ERROR_CODE, __FUNCTION__);
        }
    }
    /**
     * Author: Geetanjali(10-March-2022)
     * get mobile plan terms
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function planTerms(Request $request)
    {

        try {
            $data = [];
            $reqObj = new PlanInfoRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, PLANTERM_VALIDATION_CODE);
            } else {

                $data = PlanMobile::getMobileTerms($request->plan_id);
                if ($data) {
                    return successResponse('Plan Terms found successfully', PLANTERM_SUCCESS_CODE, $data);
                }
                return errorResponse('Plan terms not found', HTTP_STATUS_NOT_FOUND, PLANTERM_ERROR_CODE);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PLANTERM_ERROR_CODE, __FUNCTION__);
        }
    }
    /**
     * Author: (10-March-2022)
     * get mobile screen data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getMobileFilters(Request $request)
    {
        try {
            $request->merge([
                "service_id" => $request->header('serviceId')
            ]);
            $reqObj = new MobileCommonRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            if ($validator->fails()) {
                $data = $validator->errors();
                return $data;
            }
            $this->response = ConnectionType::getMobileFilters($request);
            if (empty($this->response)) {
                return errorResponse('Data not found', HTTP_STATUS_NOT_FOUND, 2039);
            }
            return successResponse('successfully', 2001, $this->response);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:(10-March-2022)
     * get mobile phone list
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getphonesList(Request $request)
    {
        try {
            $this->response = Brand::getphonesList($request);
            return successResponse('successfully', 2001, $this->response);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author: Geetanjali(10-March-2022)
     * get mobile plan critical data
     */
    public function planCriticalInfo(Request $request)
    {

        try {
            $data = [];
            $reqObj = new PlanInfoRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, PLANCRITICAL_VALIDATION_CODE);
            } else {

                $data = PlanMobile::getMobileCriticalInfo($request->plan_id);

                if ($data) {
                    return successResponse('Plan Critical Info found successfully', PLANCRITICAL_SUCCESS_CODE, $data);
                }
                return errorResponse('Plan Critical Info not found', HTTP_STATUS_NOT_FOUND, PLANCRITICAL_ERROR_CODE);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PLANCRITICAL_ERROR_CODE, __FUNCTION__);
        }
    }
    public function planDetails(Request $request)
    {

        try {
            $data = [];
            $reqObj = new PlanInfoRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {

                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, PLANDETAIL_VALIDATION_CODE);
            } else {


                $data = PlanMobile::getPlanDetails($request);

                if ($data) {
                    return successResponse('Plan details found successfully', PLANDETAIL_SUCCESS_CODE, $data);
                }
                return errorResponse('Plan details not found', HTTP_STATUS_NOT_FOUND, PLANDETAIL_ERROR_CODE);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PLANDETAIL_ERROR_CODE, __FUNCTION__);
        }
    }
    public function planCompareDetails(Request $request)
    {
        try {
            return PlanMobile::planCompareDetails($request);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PLANDETAIL_ERROR_CODE, __FUNCTION__);
        }
    }
}
