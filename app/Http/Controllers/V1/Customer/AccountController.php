<?php

namespace App\Http\Controllers\V1\Customer;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Http\Requests\Common\PersonalDetailsRequest;

class AccountController
{
    public function personalDetail(Request $request)
    {
        try {
            $reqObj = new PersonalDetailsRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            if ($validator->fails()) {
                $data = $validator->errors();
                return $data;
            }
            return Visitor::savePersonalDetails($request);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ABN_ERROR_CODE, __FUNCTION__);
        }
    }
}
