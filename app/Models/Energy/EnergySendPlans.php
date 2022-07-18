<?php

namespace App\Models\Energy;

use Illuminate\Database\Eloquent\Model;
use App\Models\{Visitor, Lead, Affiliate, PlanEnergy, AffiliateTemplates, AffiliateThirdPartyApi,PlansBroadband,PlanMobile};
use App\Traits\CommonApi\SendPlan;
use Illuminate\Support\Facades\Storage;
use App\Repositories\SparkPost\SparkPost;

class EnergySendPlans extends Model
{
    static function getSendPlanData($request,$serviceId)
    {
        $leadId = decryptGdprData($request->visit_id);
      
        $emailCc = [];
        $emailbcc = [];
        $replacedContent='';
        $elecCheckPlanExist = '';
        $elecCheckPlanExist = '';
        $gasCheckPlanExist = '';
        $broadbandPlan='';
        $gasCheckPlanExist = "";
        $mobilePlan = '';
        $affiliateKey = $request->header('API-Key');
        $visitorData = [];
        $visitor = Lead::getData(['lead_id' => $leadId], ['status', 'visitor_id', 'lead_id', 'referal_code']);
      
       
        if (!$visitor) {
           
            return errorResponse(['visitor_id' => ['Visitor Not Found']], HTTP_STATUS_VALIDATION_ERROR, CREATECUSTOMER_VALIDATION_CODE);
        }
        
        if ($visitor[0]->status == 2) {

            return successResponse('Sale is already created for this visitor.', CREATECUSTOMER_SUCCESS_CODE, ['visitr_status' => $visitor->status]);
        }else{
            $visitorData = Visitor::where('id',$visitor[0]->visitor_id)->first();
        }
       
        if (!empty($request->first_name) && !empty($request->phone)) {
            $requestData['first_name'] = $request->first_name;
            $requestData['last_name'] = $request->last_name;
            $requestData['email'] = $request->email;
            $requestData['phone'] = $request->phone;
            Visitor::where('id', $leadId)->update($requestData);
            //$leadData = Visitor::addOrUpdateData($request, $visitor);
        }else{
            $requestData['first_name'] = $visitorData->first_name;
            $requestData['last_name'] = $visitorData->last_name;
            $requestData['email'] = $visitorData->email;
            $requestData['phone'] = $visitorData->phone;
        }
        $user = auth()->user();
        //if electricity plan is set
     
        if($serviceId == 1){
            
            if ($request->has('plan_id') && $request->plan_id != '') {
                $elecCheckPlanExist = PlanEnergy::checkPlanExists($request, 1);
              
                if (empty($elecCheckPlanExist)) {
                    $response = ['message' => 'Invalid electricity plan id, please check and try again.'];
                    return errorResponse($response, HTTP_STATUS_NOT_FOUND, HTTP_DATA_NOT_FOUND);
                }
            }
           
            if ($request->has('gas_plan_id') && $request->gas_plan_id != '') {
                $gasCheckPlanExist =PlanEnergy::checkPlanExists($request, 2);
               
                if (empty($gasCheckPlanExist)) {
                   
                    $response = ['status' => 0, 'message' => 'Invalid gas plan id, please check and try again.'];
                   
                    return errorResponse($response, HTTP_STATUS_NOT_FOUND, HTTP_DATA_NOT_FOUND);
                }
            }
        }elseif($serviceId == 3){
            
           $broadbandPlan= PlansBroadband::checkPlanExists($request, 3);
           if (empty($broadbandPlan)) {
            $response = ['status' => 0, 'message' => 'Invalid  plan id, please check and try again.'];
            return errorResponse($response, HTTP_STATUS_NOT_FOUND, HTTP_DATA_NOT_FOUND);
        }
        }elseif($serviceId == 2){
          
            $mobilePlan= PlanMobile::checkPlanExists($request);
           
            
        }
     
        
        $user = auth()->user();
        $affData = Affiliate::getAffiliateData($user->id, ['*'],$affiliateKey);
       
        $affTemplate = AffiliateTemplates::getAffTemplate($request->template_type, $serviceId, $user->id);
       
        $dataArray['elec_data'] = $elecCheckPlanExist;
        $dataArray['gas_data'] = $gasCheckPlanExist;
        $dataArray['broadband_data'] = $broadbandPlan;
        $dataArray['mobile_data'] = $mobilePlan;
        $dataArray['getAffTemplate'] = $affTemplate;
      
        if ($affTemplate != null) {
            $replacedContent = self::setSendplanLink($dataArray, $request, $affData,$visitorData);
         
        } else {
            $response = ['status' => false, 'message' => 'Email template not found. Please try again later.', 'status_code' => PLANLIST_ERROR_CODE];
            return $response;
        }
        //replace content
     
        $spark = new SparkPost($request);
        $data['service_id'] = $serviceId;
        
        $getToken = $spark->getToken($data);
        if ($request->has('email')) {
            $emailId = $request->email;
        } else {
            
            $emailId = ($visitorData->email);
        }
        
        if ($affTemplate->email_cc)
            $emailCc = $affTemplate->email_cc;
        if ($affTemplate->email_bcc)
            $emailbcc = $affTemplate->email_bcc;

        $sendData = [
            "service_id" => $serviceId,
            "receiver_email" => $emailId,
            "cc_mailID" => $emailCc,
            "bcc_mailID" => $emailbcc,
            "template_id" => "$affTemplate->template_id",
            "subaccount_id" => $affData['getthirdpartyapi']->subaccount_id,
            "open_tracking" => $affTemplate->opens_tracking,
            "click_tracking" => $affTemplate->click_tracking,
            "transactional" => $affTemplate->transactional,
            "subject" => $affTemplate->subject,
            "attachments" => [],
            "mail_data" => $replacedContent
        ];
      
        $response = $spark->sendMailTemplateId($sendData, $getToken['data']->token);
        return $response;
    }
   
    static function setSendplanLink($dataArr, $request, $affData,$user)
    {
     
        $sparkPostArr = [];
        if ($dataArr['getAffTemplate'] != null) {
            $visitId = $request->visit_id;
          
            $baseUrl = $affData['getApiKeyData']->page_url;
            $slug = $affData['affiliateParameter']->slug;
            $pd = $affData['affiliateParameter']->plan_detail;
            $pl = $affData['affiliateParameter']->plan_listing;
            $termConditions =  $affData['affiliateParameter']->terms;
            $checklastTerms =  substr($termConditions, -1);

            $checklastTm =  substr($termConditions, -1);
            if($checklastTm != '/'){
                $termConditions = $termConditions.'/';
            }
            $checklast =  substr($baseUrl, -1);
            if($checklast != '/'){
                $baseUrl = $baseUrl.'/';
            }
            $pdbaseUrl = $baseUrl.$slug.'/?'.$pd;
            $plbaseUrl = $baseUrl.$slug.'/?'.$pl;
            //pd token : service_id,visit_id,Plan_id
            $serviceId = ($request->header('serviceid'));
            $planId = ($request->plan_id);
            $gas = ($request->gas_plan_id);
           
            $sparkPostArr['SignUp_Plan_Detail_Link'] =  $serviceId . "__" .($visitId) ."__".$planId."__".$gas;

            $sparkPostArr['SignUp_Plan_Listing_Link'] = $serviceId . "__" . ($visitId) ."__";
            $sparkPostArr['SignUp_Plan_Detail_Link'] = $pdbaseUrl."=" .base64_encode($sparkPostArr['SignUp_Plan_Detail_Link']);
            $sparkPostArr['SignUp_Plan_Listing_Link'] = $plbaseUrl."=" .base64_encode($sparkPostArr['SignUp_Plan_Listing_Link']);
           
            
            if (!empty($visitor['referal_code'])) {
                $sparkPostArr['SignUp_Plan_Listing_Link'] .= "&rc=" . $visitor['referal_code'];
                $sparkPostArr['SignUp_Plan_Detail_Link'] .= "&rc=" . $visitor['referal_code'];
            }

            if ($request->has('urlParam')) {
                $sparkPostArr['SignUp_Plan_Listing_Link'] .= $request->urlParam;
                $sparkPostArr['SignUp_Plan_Detail_Link'] .= $request->urlParam;
            }
            $sparkPostArr['SignUp_Plan_Detail_Link'] .="&pick_var=".$pd;
            $sparkPostArr['SignUp_Plan_Listing_Link'] .= "&pick_var=". $pl;
           
            if($request->header('serviceid') == 1){

             
            if ($request->has('template_type') && $request->template_type == 1) {

                //single electricity plan
                $sparkPostArr['electricity_provider_name'] = $dataArr['elec_data']->provider->name;
                $sparkPostArr['electricity_provider_phone_number'] = "";
                //provider term condition by removing spaces then make URL
                $parameter = str_replace(" ", "", $dataArr['elec_data']->provider->name);
                $sparkPostArr['electricity_provider_term_conditions'] = $checklastTm . 'provider-conditions/?provider=' . $parameter;

                $sparkPostArr['electricity_plan_name'] = $dataArr['elec_data']->plan_name;
                if ($request->has('plan_url')) {

                    $url = $request->plan_url;
                    $sparkPostArr['electricity_plan_detail_link'] = '<a href=' . $url . '>Electricity Plan Details</a>';
                } else {
                    if (isset($dataArr['elec_data']->show_price_fact) && $dataArr['elec_data']->show_price_fact == 1) {
                        // $path = 'Providers_Plans' . '/' . str_replace(' ', '_', decryptGdprData($dataArr['elec_data']->provider->name)) . '/' . str_replace(' ', '_', $dataArr['elec_data']->plan_name) . '/' . $dataArr['elec_data']->plan_document;
                        $disk = Storage::disk('s3_plan');
                        // $url = $disk->getAdapter()->getClient()->getObjectUrl(Config::get('filesystems.disks.s3_plan.bucket'), $path);
                        $url = $dataArr['elec_data']->plan_document;
                        $sparkPostArr['electricity_plan_detail_link'] = '<a href=' . $url . '>Electricity Plan Details</a>';
                    } else {
                        $sparkPostArr['electricity_plan_detail_link'] = 'Available On Request';
                    }
                }
            } elseif ($request->has('template_type') && $request->template_type == 2) {
                //single gas plan
                $sparkPostArr['gas_provider_name'] = $dataArr['gas_data']->provider->name;
                $sparkPostArr['gas_provider_phone_number'] = $dataArr['gas_data']->provider->phone;
                //provider term condition by removing spaces then make URL
                $parameter = str_replace(" ", "", $dataArr['gas_data']->provider->name);
                $sparkPostArr['gas_provider_term_conditions'] = $checklastTm . 'provider-conditions/?provider=' . $parameter;
                $sparkPostArr['gas_plan_name'] = $dataArr['gas_data']->plan_name;
                if ($request->has('plan_url')) {
                    $url = $request->gas_plan_url;
                    $sparkPostArr['gas_plan_detail_link'] = '<a href=' . $url . '>Gas Plan Details</a>';
                } else {
                    if (isset($dataArr['gas_data']->show_price_fact) && $dataArr['gas_data']->show_price_fact == 1) {
                        //$path = 'Providers_Plans' . '/' . str_replace(' ', '_', decryptGdprData($dataArr['gas_data']->provider->name)) . '/' . str_replace(' ', '_', $dataArr['gas_data']->plan_name) . '/' . $dataArr['gas_data']->plan_document;
                        $disk = Storage::disk('s3_plan');
                        $url = $dataArr['gas_data']->plan_document;
                        //$url = $disk->getAdapter()->getClient()->getObjectUrl(Config::get('filesystems.disks.s3_plan.bucket'), $path);
                        $sparkPostArr['gas_plan_detail_link'] = '<a href=' . $url . '>Gas Plan Details</a>';
                    } else {
                        $sparkPostArr['gas_plan_detail_link'] = 'Available On Request';
                    }
                }
            }
            
            elseif ($request->has('template_type') && $request->template_type == 3 && $dataArr['elec_data']->provider_id == $dataArr['gas_data']->provider_id) {
                $sparkPostArr['provider_name'] = $dataArr['elec_data']->provider->name;
                
                //$sparkPostArray['provider_phone_number'] = $dataArr['elec_data']->provider->phone;
                $parameter = str_replace(" ", "",$dataArr['elec_data']->provider->name);
                $sparkPostArr['provider_term_conditions'] = $affData->page_url . '/provider-term-conditions/?provider=' . $parameter;
                $sparkPostArr['electricity_plan_name'] = $dataArr['elec_data']->plan_name;
                $sparkPostArr['gas_plan_name'] = $dataArr['gas_data']->plan_name;
                
                if ($request->has('gas_plan_url')) {
                    $url = $request->gas_plan_url;
                    $sparkPostArr['gas_plan_detail_link'] = '<a href=' . $url . '>Gas Plan Details</a>';
                } else {
                    if ((isset($dataArr['gas_data']->show_price_fact) && $dataArr['gas_data']->show_price_fact == 1)) {
                        //$path = 'Providers_Plans' . '/' . str_replace(' ', '_', $dataArr['gas_data']->provider->name) . '/' . str_replace(' ', '_', $dataArr['gas_data']->plan_name) . '/' . $dataArr['gas_data']->plan_document;
                        $disk = Storage::disk('s3_plan');
                        //$url = $disk->getAdapter()->getClient()->getObjectUrl(\Config::get('filesystems.disks.s3_plan.bucket'), $path);
                        $url = $dataArr['gas_data']->plan_document;
                        $sparkPostArr['gas_plan_detail_link'] = '<a href=' . $url . '>Gas Plan Details</a>';
                    } else {
                        $sparkPostArr['gas_plan_detail_link'] = 'Available On Request';
                    }
                }
                if ($request->has('plan_url')) {
                    $url = $request->plan_url;
                    $sparkPostArr['electricity_plan_detail_link'] = '<a href=' . $url . '>Electricity Plan Details</a>';
                } else {
                    if ((isset($dataArr['elec_data']->show_price_fact) && $dataArr['elec_data']->show_price_fact == 1)) {
                        // $path = 'Providers_Plans' . '/' . str_replace(' ', '_', decryptGdprData($dataArr['elec_data']->provider->name)) . '/' . str_replace(' ', '_', $dataArr['elec_data']->plan_name) . '/' . $dataArr['elec_data']->plan_document;
                        $disk = Storage::disk('s3_plan');
                        $url = $dataArr['elec_data']->plan_document;
                        //$url = $disk->getAdapter()->getClient()->getObjectUrl(\Config::get('filesystems.disks.s3_plan.bucket'), $path);
                        //$sparkPostArray['electricity_plan_detail_link'] = url('/uploads/plan_document/'.$dataArr['elec_data']->plan_document);
                        $sparkPostArr['electricity_plan_detail_link'] = '<a href=' . $url . '>Electricity Plan Details</a>';
                    } else {
                        $sparkPostArr['electricity_plan_detail_link'] = 'Available On Request';
                    }
                }
               
            } elseif ($request->has('template_type') && $request->template_type == 4 && $dataArr['elec_data']->provider_id != $dataArr['gas_data']->provider_id) {
                $sparkPostArr['electricity_provider_name'] = $dataArr['elec_data']->provider->name;
                $sparkPostArr['electricity_provider_phone_number'] = $dataArr['elec_data']->provider->phone;
                $sparkPostArr['gas_provider_name'] = $dataArr['gas_data']->provider->name;
                $sparkPostArr['gas_provider_phone_number'] = $dataArr['gas_data']->provider->phone;
                //provider term condition by removing spaces then make URL
                $parameter = str_replace(" ", "",$dataArr['elec_data']->provider->name);
                $sparkPostArr['provider_term_conditions'] = $affData->page_url . '/provider-term-conditions/?provider=' . $parameter;

                $sparkPostArr['electricity_plan_name'] = $dataArr['elec_data']->plan_name;

                $sparkPostArr['gas_plan_name'] = $dataArr['gas_data']->plan_name;

                if ($request->has('gas_plan_url')) {
                    // if (isset($dataArr['gas_data']->show_price_fact)) {
                    $url = $request->gas_plan_url;
                    $sparkPostArr['gas_plan_detail_link'] = '<a href=' . $url . '>Gas Plan Details</a>';
                    // } else {
                    // 	$sparkPostArray['gas_plan_detail_link'] = 'Available On Request';
                    // }
                } else {
                    if ((isset($dataArr['gas_data']->show_price_fact) && $dataArr['gas_data']->show_price_fact == 1)) {

                        //$path = 'Providers_Plans' . '/' . str_replace(' ', '_', $dataArr['gas_data']->provider->name) . '/' . str_replace(' ', '_', $dataArr['gas_data']->plan_name) . '/' . $dataArr['gas_data']->plan_document;
                        $disk = Storage::disk('s3_plan');

                        // $url = $disk->getAdapter()->getClient()->getObjectUrl(\Config::get('filesystems.disks.s3_plan.bucket'), $path);
                        //$sparkPostArray['gas_plan_detail_link'] = url('/uploads/plan_document/'.$dataArr['gas_data']->plan_document);
                        $url = $dataArr['gas_data']->plan_document;

                        $sparkPostArr['gas_plan_detail_link'] = '<a href=' . $url . '>Gas Plan Details</a>';
                    } else {

                        $sparkPostArr['gas_plan_detail_link'] = 'Available On Request';
                    }
                }
                if ($request->has('plan_url')) {

                    $url = $request->plan_url;
                    $sparkPostArr['electricity_plan_detail_link'] = '<a href=' . $url . '>Electricity Plan Details</a>';
                } else {

                    if ((isset($dataArr['elec_data']->show_price_fact) && $dataArr['elec_data']->show_price_fact == 1)) {

                        // $path = 'Providers_Plans' . '/' . str_replace(' ', '_', decryptGdprData($dataArr['elec_data']->provider->name)) . '/' . str_replace(' ', '_', $dataArr['elec_data']->plan_name) . '/' . $dataArr['elec_data']->plan_document;
                        $disk = Storage::disk('s3_plan');
                        //$url = $disk->getAdapter()->getClient()->getObjectUrl(\Config::get('filesystems.disks.s3_plan.bucket'), $path);
                        $url = $dataArr['elec_data']->plan_document;

                        $sparkPostArr['electricity_plan_detail_link'] = '<a href=' . $url . '>Electricity Plan Details</a>';
                    } else {

                        $sparkPostArr['electricity_plan_detail_link'] = 'Available On Request';
                    }
                }
            }
        }elseif($request->header('serviceid') == 3){
        }elseif($request->header('serviceid') == 2){
        }
       // $sparkPostArr['SignUp_Plan_Listing_Link'] = '<a href=' . $sparkPostArr['SignUp_Plan_Listing_Link'] . '>Plan Listing</a>';
           
       // $sparkPostArr['SignUp_Plan_Detail_Link'] = '<a href=' . $sparkPostArr['SignUp_Plan_Detail_Link'] . '>Plan Details</a>';
        }
       
        $sparkPostArray = SendPlan::setAffiliateArray($request, $user, $affData,$dataArr,$request->template_type);
       
        $sparkPostArray = array_merge($sparkPostArray,$sparkPostArr);
        
       
        return  $sparkPostArray;
    }
}
