<?php

namespace App\Http\Controllers\V1\Distributor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Distributor;
use Illuminate\Support\Facades\Validator;

class DistributorController extends Controller
{
    /**
     * Fetch distributor lists w.r.t postcode.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDistributorList(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                Distributor::rules(),
                Distributor::messages()
            );

            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, 2002);
            }

            $result = [];
            $distributorList = Distributor::getDistributor($request);

            if ($distributorList) {
                foreach ($distributorList['elec_distributor'] as $elecArr) {
                    $result['electricity_distributor'][] = ['distributor_key' => $elecArr['distributor']['id'], 'distributor_name' => $elecArr['distributor']['name']];
                }

                foreach ($distributorList['gas_distributor'] as $gasArr) {
                    $result['gas_distributor'][] = ['distributor_key' => $gasArr['distributor']['id'], 'distributor_name' => $gasArr['distributor']['name']];
                }

                return successResponse('Distributor list is found', 2001, $result);
            }

            $result['gas_distributor'][] = ['distributor_key' => 'idontknow', 'distributor_name' => 'I Dont Know'];
            $result['electricity_distributor'][] = ['distributor_key' => 'idontknow', 'distributor_name' => 'I Dont Know'];
            return successResponse('Distributor list is found', 2001, $result);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
}
