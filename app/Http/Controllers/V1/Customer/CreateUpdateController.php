<?php

namespace App\Http\Controllers\V1\Customer;

use App\Models\{Visitor, Lead, Setting,Marketing,VisitorAddress,VisitorIdentification,VisitorInformationEnergy};
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Energy\{CreateMoveInCustomer, SaveAuthTokenRequest};

class CreateUpdateController
{
    /**
     * Create Customer.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        try {
            DB::beginTransaction();
            $action = $request->segment(3);
            $validator = Validator::make(
                $request->all(),
                Visitor::customerRules(),
                Visitor::customerMessages()
            );

            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, CREATECUSTOMER_VALIDATION_CODE);
            }

            $leadId = decryptGdprData($request->visit_id);

            $visitor = Lead::getFirstLead(['lead_id' => $leadId], ['status', 'visitor_id', 'lead_id', 'post_code', 'address', 'affiliate_id']);

            if (!$visitor) {
                return errorResponse(['visit_id' => ['Visitor Not Found']], HTTP_STATUS_VALIDATION_ERROR, CREATECUSTOMER_VALIDATION_CODE);
            }

            if ($visitor->status == 2) {
                return successResponse('Sale is already created for this visitor.', CREATECUSTOMER_SUCCESS_CODE, ['visitor_status' => $visitor->status]);
            }

            Visitor::addOrUpdateData($request, $visitor);
            $remarketingToken = Visitor::getRemarketingToken($leadId);
            $isDmoState = Setting::isDmoState($visitor);
            DB::commit();
            return successResponse($action == 'create' ? 'Visitor created successfully' : 'Visitor updated successfully', CREATECUSTOMER_SUCCESS_CODE, ['directmarketing' => $remarketingToken, 'is_dmo_state' => $isDmoState]);
        } catch (\Exception $e) {
            DB::rollback();
            return errorResponse($e->getMessage() . " on line:" . $e->getLine() . " file:" . $e->getFile(), $e->getCode(), CREATECUSTOMER_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Update Phone Number.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePhoneNumber(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                Visitor::phoneRules(),
                Visitor::phoneMessages()
            );

            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, CREATECUSTOMER_VALIDATION_CODE);
            }

            $leadId = decryptGdprData($request->visit_id);

            $visitor = Lead::getFirstLead(['lead_id' => $leadId], ['status', 'visitor_id', 'lead_id']);

            if (!$visitor) {
                return errorResponse(['visit_id' => ['Visitor Not Found']], HTTP_STATUS_VALIDATION_ERROR, CREATECUSTOMER_VALIDATION_CODE);
            }


            $num = phoneNumber($request->phone);
            if (Visitor::updateData([['id', $visitor->visitor_id], ['phone', '!=', encryptGdprData($request->phone) ]], ['phone' => encryptGdprData($num)], $visitor)) {
                return successResponse('The Phone number has been successfully updated', CREATECUSTOMER_SUCCESS_CODE);
            }

            return successResponse('Entered Phone number is same so please proceed with same no.', CREATECUSTOMER_SUCCESS_CODE);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), CREATECUSTOMER_ERROR_CODE, __FUNCTION__);
        }
    }

    public function createMoveInCustomer(Request $request){
        try{
            
            $reqObj = new CreateMoveInCustomer($request);
            $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
            $status = true;
            $statusCode = HTTP_STATUS_OK;
            $message = HTTP_SUCCESS;
           
    
            if ($validator->fails()) {
                $status = false;
                $statusCode = HTTP_STATUS_VALIDATION_ERROR;
                $message = HTTP_ERROR;
                $data = $validator->errors();
    
                return response()->json([
                    'status' => $status,
                    'message' => $message,
                    'response' => $data,
                ], $statusCode);
            } 

            if($request->connection_address !=''){
                $addressData['address'] =$request->connection_address;
                $addressData['post_code'] =$request->post_code;
                $addressData['address_type'] =1;
                $id = VisitorAddress::create($addressData);
                
                $request->request->add(['connection_address_id'=>$id->id]);
            }
            $leadId = Lead::createVisit($request);
            $visitId = Visitor::saveVisitor($request);
            
            $request->request->add(['visit_id'=> encryptGdprData($leadId)]);
            $data =  Lead::SaveJourneyData($request);
            $marketingData['rc'] =  $request->rc;
            $marketingData['cui'] =  $request->cui;
            $marketingData['utm_source'] =  $request->utm_source;
            $marketingData['utm_medium'] =  $request->utm_medium;
            $marketingData['utm_campaign'] =  $request->utm_campaign;
            $marketingData['utm_term'] =  $request->utm_term;
            $marketingData['utm_content'] =  $request->utm_content;
            $marketingData['gclid'] =  $request->gclid;
            $marketingData['fbclid'] =  $request->fbclid;
             $marketing = Marketing::addParamaeters($leadId, $marketingData);
           
            $vIdentification =  VisitorIdentification::saveIdentification($request);
            $jointAccount =  VisitorInformationEnergy::saveJointAccountDetails($request,$visitId->id);
          
           
           if($marketing){
            return successResponse('Customer created successfully ', CREATECUSTOMER_SUCCESS_CODE,encryptGdprData($leadId));
           }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), CREATECUSTOMER_ERROR_CODE, __FUNCTION__);
        }
          
    }
}
