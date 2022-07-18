<?php

namespace App\Http\Controllers\V1\Customer;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Repositories\Abn\AbnRepository;

class AbnController
{
    /**
     * Search ABN by name.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAbnName(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['search' => 'required'], ['search.required' => 'Search value is required.']);
            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, ABN_VALIDATION_CODE);
            }

            $abn = new AbnRepository;
            $result = $abn->Abnbyname($request->search);
            if ($result) {
                return successResponse('ABN details are found based on current search.', ABN_SUCCESS_CODE, $result);
            }

            return errorResponse('No record found based on current search.', HTTP_STATUS_VALIDATION_ERROR, ABN_VALIDATION_CODE);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ABN_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Search ABN by number.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchAbnNumber(Request $request)
	{
		try {
			$validator = Validator::make($request->all(), ['search' => 'required'], ['search.required' => 'Search value is required.']);
            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, ABN_VALIDATION_CODE);
            }

            $abn = new AbnRepository;
			$result = $abn->Abnbynumber($request->search);
            if ($result) {
                return successResponse('ABN number details are found successfully.', ABN_SUCCESS_CODE, $result);
            }
                
			return errorResponse('No record found based on current search.', HTTP_STATUS_VALIDATION_ERROR, ABN_VALIDATION_CODE);

		} catch (\Exception $e) {
			return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), CREATECUSTOMER_ERROR_CODE, __FUNCTION__);
		}
	}
}