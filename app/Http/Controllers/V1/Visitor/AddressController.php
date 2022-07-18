<?php

namespace App\Http\Controllers\V1\Visitor;

use Illuminate\Support\Facades\ { DB, Validator };
use Illuminate\Http\Request;
use App\Models\ { VisitorAddress, Lead };

class AddressController
{
    /**
     * Save address details.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make(
                $request->all(),
                ['visit_id' => 'required'],
                ['visit_id.required' => 'Visit id is required']
            );

            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, ADDRESS_VALIDATION_CODE);
            }
            $leadId = decryptGdprData($request->visit_id);
            $visitor = Lead::getFirstLead(['lead_id' => $leadId], ['lead_id','status','visitor_id','connection_address_id','billing_address_id','delivery_address_id']);
            if (!$visitor->visitor_id || !trim($visitor->visitor_id)) {
                return errorResponse('Visitor not found', HTTP_STATUS_VALIDATION_ERROR, ADDRESS_VALIDATION_CODE);
            }

            $response = VisitorAddress::addAddress($request, $visitor);
            if (is_array($response)) {
                $validator = Validator::make(
                    $request->all(),
                    $response['rules'],
                    $response['message']
                );
                
                if ($validator->fails()) {
                    return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, ADDRESS_VALIDATION_CODE);
                }
            }
            
            DB::commit();
            return successResponse('Address saved successfully.',  ADDRESS_SUCCESS_CODE);
        } catch (\Exception $e) {
            DB::rollback();
            dd($e->getMessage());
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ADDRESS_ERROR_CODE, __FUNCTION__);
        }
    }
}
