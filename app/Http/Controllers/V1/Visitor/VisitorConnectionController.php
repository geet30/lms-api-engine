<?php

namespace App\Http\Controllers\V1\Visitor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Common\VisitConnectionRequest;
use App\Models\{MobileConnectionDetails, BroadbandConnectionDetails};

class VisitorConnectionController extends Controller
{
    /**
     * Author:Harsimran(25-March-2022)
     * save connection detail data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    public function saveConnectionDetails(Request $request)
    {
        try {
            $request->merge([
                "service_id" => $request->header('serviceId')
            ]);
            $reqObj = new VisitConnectionRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            if ($validator->fails()) {
                $data = $validator->errors();
                return $data;
            }
            if ($request->service_id == "3") {
                return BroadbandConnectionDetails::saveConnectionDetails($request);
            }
            if ($request->service_id == "2") {
                return MobileConnectionDetails::saveConnectionDetails($request);
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
}
