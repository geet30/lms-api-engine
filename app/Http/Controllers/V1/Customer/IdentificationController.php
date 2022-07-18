<?php

namespace App\Http\Controllers\V1\Customer;

use Illuminate\Support\Facades\ { DB, Validator };
use Illuminate\Http\Request;
use App\Models\VisitorIdentification;

class IdentificationController
{
    /**
     * Save identification details.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        DB::beginTransaction();
        try {
            $ruleMes = VisitorIdentification::rulesAndMessages();
            $validator = Validator::make(
                $request->all(),
                $ruleMes['rules'],
                $ruleMes['messages']
            );

            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, IDENTIFICATION_VALIDATION_CODE);
            }

            VisitorIdentification::clearIdentification($request->visit_id);

            VisitorIdentification::addIdentification($request);
            DB::commit();
            return successResponse('Identification Details saved Successfully.',  IDENTIFICATION_SUCCESS_CODE);
        } catch (\Exception $e) {
            DB::rollBack();
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), IDENTIFICATION_ERROR_CODE, __FUNCTION__);
        }
    }
}
