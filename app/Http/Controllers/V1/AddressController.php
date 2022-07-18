<?php

namespace App\Http\Controllers\V1;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ { 
    PlansBroadband
};
use App\Http\Requests\Broadband\{
    SearchAddressRequest,
    RetriveAddressRequest,
    PlanListRequest,
};
use  App\Repositories\Address\GetAddressDetail;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->addressRepository = new GetAddressDetail();
    }


    /**
     * 
     */
    public function searchAddress(Request $request)
    {
        try{
            $request = $request->all();
            $result = [];
            
            $reqObj = new SearchAddressRequest($request);
            $validator = Validator::make($request, $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            } else {

                $result = $this->addressRepository->searchAddressDetails($request['search_address']);
                if (empty($result)) {
                    return errorResponse('Address not found. Please enter correct address and select it from dropdown.', HTTP_STATUS_NOT_FOUND, 2039);
                }
            }
            return successResponse('Address(es) found successfully with given search string.', 2001, $result);

        } catch(\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

    /**
     * 
     */
    public function retrieveAddress(Request $request)
    {
        try{
            $request = $request->all();
            $result = [];
            
            $reqObj = new RetriveAddressRequest($request);
            $validator = Validator::make($request, $reqObj->rules(), $reqObj->messages());

            if ($validator->fails()) {
                $data = $validator->errors();
                return errorResponse($data, HTTP_STATUS_VALIDATION_ERROR, 2002);
            } else {

                $result = $this->addressRepository->retrieveAddressData($request['record_id']);//Gnaf7133662051
                if (empty($result)) {
                    return errorResponse('Address not found. Please enter correct address and select it from dropdown.', HTTP_STATUS_NOT_FOUND, 2039);
                }
            }
            return successResponse('Full address found successfully for given record id.', 2001, $result);
        } catch(\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

}
