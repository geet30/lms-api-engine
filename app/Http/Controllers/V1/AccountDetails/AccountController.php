<?php

namespace App\Http\Controllers\V1\AccountDetails;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\AccountDetails\{ SaveAuthTokenRequest};
use App\Traits\LambdaTokenEx\LambdaTokenEx;
class AccountController extends Controller{

    use LambdaTokenEx;
    /**
     * Author:Geetanjali(4-april-2022)
     * getAuthToken
     */
    public function getAuthToken(Request $request)
    {
        
        try {
            $tokenizedAuth = tokenizedAuth($request);
            if (!$tokenizedAuth) {
                return errorResponse('Token Not Found', HTTP_STATUS_NOT_FOUND, LMD_ERROR_CODE);
            } else {
                return successResponse('Token Found Successfully', LMD_SUCCESS_CODE, $tokenizedAuth);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, LMD_ERROR_CODE, __FUNCTION__);
        }
    }
    public function deTokenizedAuth(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['token_value' => 'required'], ['token_value.required' => 'token value is required.']);
            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, LMD_VALIDATION_CODE);
            }

            $detokenizedAuth = detokenizedAuth($request);
            if (!$detokenizedAuth) {
                return errorResponse('Token Not Found', HTTP_STATUS_NOT_FOUND, LMD_ERROR_CODE);
            } else {
                return successResponse('Detokonized Successfully', LMD_SUCCESS_CODE, $detokenizedAuth);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, LMD_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Author:Geetanjali(4-april-2022)
     * saveAuthToken
     */
    public function saveAuthToken(Request $request)
    {
        try {

            $reqObj = new SaveAuthTokenRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {

                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, AUTH_VALIDATION_CODE);
            } else {
                $data =  $this->saveTokenEx($request);
                if ($data) {
                    return successResponse('Record saved successfully', AUTH_SUCCESS_CODE, $data);
                }
                return errorResponse('Record not saved', HTTP_STATUS_NOT_FOUND, AUTH_ERROR_CODE);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, AUTH_ERROR_CODE, __FUNCTION__);
        }
    }
}