<?php

namespace App\Http\Controllers\V1\Visitor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SolarType;
use Illuminate\Support\Facades\Validator;

class SolarController extends Controller
{
    /**
     * Get solar type.
     * Author: Sandeep Bangarh
    */
    public function getSolarTypeList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['post_code' => 'required'], ['post_code.required' => 'post code is required']);
            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, 2002);
            } 

            $address = explode(',', $request->post_code);
            if (count($address) != 3) {
                return errorResponse("Please enter either your post-code or suburb and then select from the list provided.", 422, 2002);
            } 

            $result = SolarType::getData(['state_code' => trim($address[2])], ['id','state_code','is_premium','is_normal']);
            if (!$result->isEmpty()) {
                return successResponse('Please check solar tariff plans.', 2001, $result->toArray());
            }

            return successResponse('No data found.', 2001, $result);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2006, __FUNCTION__);
        }
    }
}
