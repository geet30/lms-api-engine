<?php

namespace App\Http\Controllers\V1\ProviderManageSetting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\{ApplyNowContentRequest, ProviderManageRequest};
use Illuminate\Support\Facades\Validator;
use App\Models\{PlanMobile};

class ProviderManageSetting extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    /**
     * Author: Geetanjali(22-March-2022)
     * get apply now content
     */
    public function applyNowContent(Request $request)
    {

        try {
            $data = [];
            $reqObj = new ApplyNowContentRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {

                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, APPLYCONTENT_VALIDATION_CODE);
            } else {

                $data = PlanMobile::getApplyNowContent($request);
                // echo "<pre>";print_r($data);die('test');
                if ($data) {
                    return successResponse('Apply Now Content found successfully', APPLYCONTENT_SUCCESS_CODE, $data);
                }
                return errorResponse('Apply Now Content not found', HTTP_STATUS_NOT_FOUND, APPLYCONTENT_ERROR_CODE);
            }
        } catch (\Exception $e) {

            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, APPLYCONTENT_ERROR_CODE, __FUNCTION__);
        }
    }

    public function providerSections(Request $request)
    {

        $data = [];
        try {

            $reqObj = new ProviderManageRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, PROVIDERSECTION_VALIDATION_CODE);
            } else {

                $data = PlanMobile::getProviderManageSection($request);
                if ($data) {
                    return successResponse('Provider Section found successfully', PROVIDERSECTION_SUCCESS_CODE, $data);
                }
                return errorResponse('Provider Section not found', HTTP_STATUS_NOT_FOUND, PROVIDERSECTION_ERROR_CODE);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PROVIDERSECTION_ERROR_CODE);
        }
    }
}
