<?php

namespace App\Http\Controllers\V1\Visitor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Visitor, Provider, Lead, StreetCodes};
use App\Models\Energy\EnergyLeadJourney;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use  App\Http\Requests\Common\VisitIdRequest;

class VisitorController extends Controller
{
    /**
     * Save Visitor Information.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveVisit(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make(
                $request->all(),
                Visitor::rules(),
                Visitor::messages()
            );

            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, 2002);
            }
           
            $leadId = Lead::createVisit($request);
            if ($leadId) {
                DB::commit();
                return successResponse('Visit saved successfully', 2001, ['visit_id' => encryptGdprData($leadId)]);
            }

            return errorResponse("Oops! We couldn't find that address. Please check and try again.", HTTP_STATUS_VALIDATION_ERROR, 2004);
        } catch (\Exception $e) {
            DB::rollback();
            return errorResponse($e->getMessage(), $e->getCode(), 2006, __FUNCTION__);
        }
    }

    /**
     * Update Visitor Information.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVisit(Request $request)
    {
        try {

            $validator = Validator::make(
                $request->all(),
                Visitor::rules('update'),
                Visitor::messages('update')
            );

            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, 2002);
            }

            $ignoreParams = EnergyLeadJourney::setIgnoreParameters($request);

            $leadId = decryptGdprData($request->visit_id);
            if (EnergyLeadJourney::updatePercentage($leadId, $request)) {
                return successResponse("Percentage updated successfully", 2001, ['ignored_params' => $ignoreParams]);
            }
            return successResponse("Percentage check given percentage or lead id is not valid", 2001, ['ignored_params' => $ignoreParams]);
        } catch (\Exception $err) {
            return errorResponse($err->getMessage(), HTTP_STATUS_SERVER_ERROR, 2006);
        }
    }

    /**
     * Update Visitor Information.
     * Author: Harsimranjit
     * @return \Illuminate\Http\Response
     */
    public function getStreetCodes()
    {
        try {
            return StreetCodes::getStreetCodes();
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * get master details.
     * @return \Illuminate\Http\Response
     */
    public function getMasterDetails(Request $request)
    {
        try {
            $reqObj = new VisitIdRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            if ($validator->fails()) {
                $data = $validator->errors();
                return $data;
            }
            return Provider::getMasterDetails($request);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
}
