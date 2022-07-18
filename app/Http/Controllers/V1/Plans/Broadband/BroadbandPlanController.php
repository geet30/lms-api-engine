<?php

namespace App\Http\Controllers\V1\Plans\Broadband;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\Nbn\Nbn;
use App\Models\{ConnectionType, Provider, PlansBroadband, MoveInCalender, SaleProductsBroadband, SaleProductsBroadbandAddon};
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Broadband\{BroadbandCommonRequest, PlanListRequest, PlanAddonListRequest, PlanAddonRequest, SatelliteRequest};
use App\Http\Requests\Common\SessionIdCheckRequest;
use App\Http\Requests\Common\{MoveInRequest, EicContentRequest, ProviderListRequest};

class BroadbandPlanController extends Controller
{
    use Nbn;
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
     * Author:Harsimran(11-March-2022)
     * get NBN data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getNbnData(Request $request)
    {
        try {
            $request->merge([
                "service_id" => $request->header('serviceId')
            ]);
            $reqObj = new BroadbandCommonRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            if ($validator->fails()) {
                $data = $validator->errors();
                return $data;
            }
            $login =  $this->login($request);
            if ($login['status'] != '200') {
                return errorResponse('Something went wrong while log in', HTTP_STATUS_SERVER_ERROR, 2002);
            };
            $request->merge([
                'token' => $login['data']->token
            ]);
            $this->response =  $this->getNbhAddress($request);
            if ($this->response['status'] == 200) {
                return successResponse('successfully', 2001, $this->response['data']->data);
            }
            return errorResponse('Something went wrong while fetching data', HTTP_STATUS_SERVER_ERROR, 2002);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(11-March-2022)
     * get NBN data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getConnectionType(Request $request)
    {
        try {
            $this->response = ConnectionType::getConnectionType($request);
            return successResponse('successfully', 2001, $this->response);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(11-March-2022)
     * get Broadband provider  data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProviderList(Request $request)
    {
        try {

            $request->merge([
                "service_id" => $request->header('serviceId')
            ]);
            $reqObj = new ProviderListRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            }
            $this->response = Provider::getProviderList($request);
            return successResponse('successfully', 2001, $this->response);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(16-March-2022)
     * get EIC data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getEicData(Request $request)
    {
        try {
            $request->merge([
                "service_id" => $request->header('serviceId')
            ]);
            // if($request->header('serviceId') != 2){
            //     $reqObj = new EicContentRequest($request);
            //     $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            //     if ($validator->fails()) {
            //         $data = $validator->errors();
            //         return $data;
            //     }
            // }
            $data = PlansBroadband::getEicData($request);
            if (empty($data)) {
                return errorResponse('Data not found', HTTP_STATUS_NOT_FOUND, 2039);
            }
            return successResponse('Data found successfully', 2001, $data);
        } catch (\Exception $e) {
            // return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002);
            return errorResponse($e->getMessage() . ' on line: ' . $e->getLine() . ' in file: ' . $e->getFile(), HTTP_STATUS_SERVER_ERROR, 2039, __FUNCTION__);
        }
    }
    public function getPlans(Request $request)
    {
        try {
            $request = app('request');
            $data = [];
            $reqObj = new PlanListRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            } else {
                $request['visit_id'] = decryptGdprData($request->input('visit_id'));
                PlansBroadband::saveJourneyData($request);
                $data = PlansBroadband::getPlanList($request);

                if (empty($data) || $data['status'] == false) {
                    return errorResponse('Plan not found', HTTP_STATUS_NOT_FOUND, 2039);
                }
            }
            return successResponse('Plan found successfully', $data['response'], 2001);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . ' on line: ' . $e->getLine() . ' in file: ' . $e->getFile(), HTTP_STATUS_SERVER_ERROR, 2039, __FUNCTION__);
        }
    }

    public function getPlansAddon(Request $request)
    {
        try {
            $request = app('request');
            $data = [];
            $reqObj = new PlanAddonListRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            } else {

                $data = PlansBroadband::getPlanAddonList($request);

                if (empty($data) || $data['status'] == false) {
                    return errorResponse('Plan not found', HTTP_STATUS_NOT_FOUND, 2039);
                }
            }
            return successResponse('Plan found successfully', $data['response'], 2001);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }

    public function savePlansAddon(Request $request)
    {
        try {
            $request = app('request');
            $data = [];
            $reqObj = new PlanAddonRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            } else {

                $data = SaleProductsBroadbandAddon::postPlanAddon($request);

                if (empty($data) || $data['status'] == false) {
                    return errorResponse($data['message'], HTTP_STATUS_NOT_FOUND, 2039);
                }
            }
            return successResponse($data['message'], 2001);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }

    public function deleteSelectedPlanAddon(Request $request)
    {
        try {
            $request = app('request');
            $data = [];
            $reqObj = new PlanAddonRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            } else {

                $data = SaleProductsBroadbandAddon::deletePlanAddonData($request->sale_product_id);

                if (empty($data) || $data['status'] == false) {
                    return errorResponse($data['message'], HTTP_STATUS_NOT_FOUND, 2039);
                }
            }

            return successResponse($data['message'], 2001);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    /**
     * Author:Harsimran(16-March-2022)
     * get Move in date data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getMinSelectableDate(Request $request)
    {
        try {
            $request->merge([
                "service_id" => $request->header('serviceId')
            ]);
            $reqObj = new MoveInRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            if ($validator->fails()) {
                $data = $validator->errors();
                return $data;
            }
            $this->response =  MoveInCalender::getMinSelectableDate($request);
            return successResponse('successfully', 2001, $this->response);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(28-March-2022)
     * save utm parameter of broadband
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveRmUtm(Request $request)
    {
        try {
            return SaleProductsBroadband::saveRmUtm($request);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(31-March-2022)
     * save utm parameter of broadband
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveSatelliteQuestion(Request $request)
    {
        try {
            $reqObj = new SatelliteRequest($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            }
            return SaleProductsBroadband::saveSatelliteQuestion($request);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
}
