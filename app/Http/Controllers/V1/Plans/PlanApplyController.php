<?php

namespace App\Http\Controllers\V1\Plans;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Nbn\Nbn;
use App\Models\{SaleProductsBroadband, SaleProductsEnergy, SaleProductsMobile, Lead};
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Plan\{PlanApplyRequest};

class PlanApplyController extends Controller
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
    }
    /**
     * Author:(22-March-2022)
     * apply plan
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function planApply(Request $request)
    {
        try {
            $request = app('request');
            $data = [];
            $reqObj = new PlanApplyRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            }
            
            $leadId = decryptGdprData($request->input('visit_id'));
            $visitor = Lead::getFirstLead(['lead_id' => $leadId], ['lead_id', 'email', 'phone', 'affiliate_id'], true);
            if (!$visitor) {
                return errorResponse('Visitor not found', HTTP_STATUS_VALIDATION_ERROR, 2002);
            }
            $service_id = $request->header('serviceId');
            $request['visit_id'] = $leadId;
            if ($service_id == 1) {
                SaleProductsEnergy::removeEnergySaleProductsData($request->input('visit_id'));
                if ($request->has('plan_id') && $request->input('plan_id') != '') {
                    $data = SaleProductsEnergy::saveEnergySaleProductsData($request, 1, $visitor);
                }
                if ($request->has('gas_plan_id') && $request->input('gas_plan_id') != '') {
                    $request['plan_id'] = $request->input('gas_plan_id');
                    $request['cost_id'] = $request->input('gas_cost_id');
                    $request['cost'] = $request->input('gas_cost');
                    $data = SaleProductsEnergy::saveEnergySaleProductsData($request, 2, $visitor);
                }
            } elseif ($service_id == 2) {
                $data = SaleProductsMobile::saveMobileSaleProductsData($request, $visitor);
            } elseif ($service_id == 3) {
                $data = SaleProductsBroadband::saveBroadbandSaleProductsData($request, $visitor);
            }
            if (empty($data) || $data['status'] == false) {
                return errorResponse($data['message'], HTTP_STATUS_NOT_FOUND, 2039);
            }

            return successResponse($data['message'], 2001);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . ' Line no.' . $e->getLine(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

    /**
     * Save Sim Time.
     * Author: Amandep Singh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addSimType(Request $request)
    {
        try {
            // $validator = Validator::make(
            //     $request->all(),
            //     Visitor::phoneRules(),
            //     Visitor::phoneMessages()
            // );

            // if ($validator->fails()) {
            //     return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, CREATECUSTOMER_VALIDATION_CODE);
            // }

            $leadId = decryptGdprData($request->visit_id);

            $visitor = Lead::getFirstLead(['lead_id' => $leadId], ['status', 'visitor_id', 'lead_id']);

            if (!$visitor) {
                return errorResponse(['visit_id' => ['Visitor Not Found']], HTTP_STATUS_VALIDATION_ERROR, CREATECUSTOMER_VALIDATION_CODE);
            }

            $sim_type = $request->sim_type;
            if (SaleProductsMobile::updateData(['lead_id' => $leadId], ['sim_type' => $sim_type])) {
                return successResponse('Sim type successfully updated', CREATECUSTOMER_SUCCESS_CODE);
            }

            return successResponse('Data not updated.', CREATECUSTOMER_SUCCESS_CODE);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), CREATECUSTOMER_ERROR_CODE, __FUNCTION__);
        }
    }
}
