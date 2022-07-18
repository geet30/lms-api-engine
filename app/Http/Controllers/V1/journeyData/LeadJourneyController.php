<?php

namespace App\Http\Controllers\V1\journeyData;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Energy\{PlanListRequest};
use App\Models\{Lead, Energy\EnergyLeadJourney,LeadJourneyDataMobile,LeadJourneyDataMobileHandset,VisitorAddress};

class LeadJourneyController extends Controller
{
    protected $lead;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->lead = new Lead();
        $this->mobileLead = new LeadJourneyDataMobile();
        
    }
    /**
     * Author:Kirti Pathania(14-april-2022)
     * save journey data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function  postSaveJourney(Request $request)
    {   
        $data = [];
        $reqObj = new PlanListRequest($request);
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
        } else {
            $serviceId = $request->header('ServiceId');
          
            $response = successResponse('Journey Data Not found', HTTP_ERROR);
            /** Add product data (Sandeep Bangarh) **/
            $model = getProductModel();
            $model::addProductData($request);
            /** End **/
            if ($serviceId == 1) {
                $this->lead::SaveJourneyData($request);
                $token =  base64_encode($request->visit_id);
               
               
                $response = successResponse('Journey Data saved successfully', CREATECUSTOMER_SUCCESS_CODE, ['ecvid_token' => $token]);
            }elseif($serviceId == 2){
               $data =  $this->mobileLead::saveMobileJourneyData($request);
               if($data->lead_id){
                    
                $token =  base64_encode(encryptGdprData($data->lead_id));
                
                $response = successResponse('Journey Data saved successfully', CREATECUSTOMER_SUCCESS_CODE, ['mcvid_token' => $token]);
               }   
            }elseif($serviceId == 3){
               $this->lead::saveBroadbandJourneyData($request);
               $response = successResponse('Journey Data saved successfully', CREATECUSTOMER_SUCCESS_CODE);
            }

            return $response;
        }
    }
    public function  getJourneyData(Request $request)
    {

        $validator = Validator::make($request->all(), ['visit_id' => 'required'], ['visit_id.required' => 'visit_id field is required or you are passing wrong parameter for visit_id']);


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
        } else {
            $data = [];
            $serviceId = $request->header('ServiceId');
        
            if ($serviceId == 1) {
                $data['journey_data'] = Lead::getData(['leads.lead_id' => decryptGdprData($request->visit_id)], ['post_code','connection_address_id', 'is_duplicate', 'status', 'energy_bill_details.*', 'lead_journey_data_energy.*'], NULL, NULL, 'journey', NULL, 'bill_details');
              
                if(isset($data['journey_data'][0]->connection_address_id)){
                    $postCode =  VisitorAddress::where('id',$data['journey_data'][0]->connection_address_id)->select('postcode','suburb','state')->first();
                }else{
                    $postCode = 0;
                }    
              
               $journeyData['journey_data']= Lead::setJaurneryResponse($data['journey_data'],$postCode);
             
                $journeyData['visitor_data'] = Lead::getData(['leads.lead_id' => decryptGdprData($request->visit_id)], ['*'], 'visitors');
                if (isset($data[0]->status) && $data[0]->status == 2) {
                    return successResponse('Sale is already created for this visitor.', CREATECUSTOMER_SUCCESS_CODE, ['visitor_status' => $data[0]->status]);
                }
                
            }elseif($serviceId == 2){
              $journeyData['journey_data'] =   $this->mobileLead::getMobileLeadData(decryptGdprData($request->visit_id));
              $journeyData['visitor_data'] = Lead::getData(['leads.lead_id' => decryptGdprData($request->visit_id)], ['*'], 'visitors');
            
              if($journeyData){
                $response = successResponse('Journey Data found', HTTP_STATUS_OK,  $journeyData);
              }else{
                $response = successResponse('Journey Data Not found', HTTP_STATUS_NOT_FOUND,  $journeyData);
              }
             
            }
            elseif($serviceId == 3){
                $journeyData['journey_data'] = Lead::getJourneyBroadbandData(decryptGdprData($request->visit_id)) ;
            }
            $response = successResponse('Journey Data Not found', HTTP_STATUS_NOT_FOUND,  $journeyData);
            if ($journeyData)
                $response = successResponse('Journey Data found', HTTP_STATUS_OK,  $journeyData);
            return $response;
        }
    }
}
