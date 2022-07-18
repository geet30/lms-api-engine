<?php

namespace App\Traits\CommonApi;

trait SendPlan
{
    /**
     * Author:Harsimran(16-March-2022)
     * get Move in date data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static function setAffiliateArray($request, $user, $affData,$providerData, $templateType)
    {
        $sparkPostArray = [];
       
        //$sparkPostArray['affiliate_contact_us'] = decryptGdprData($affData->support_phone_number);

        if ($request->has('email')) {
            $sparkPostArray['customer_name']
                = $request->first_name . ' ' . $request->last_name;
            $sparkPostArray['customer_email'] = $request->email;
            $sparkPostArray['customer_number'] = $request->phone;
        } else {
            $sparkPostArray['customer_name'] = ($user->first_name) .' '. ($user->last_name);
            $sparkPostArray['customer_email'] = ($user->email);
            $sparkPostArray['customer_number'] = ($user->phone);
        }
        $sparkPostArray['twitter'] = $affData->twitter_url;
        $sparkPostArray['youtube'] = $affData->youtube_url;
        $sparkPostArray['facebook'] = $affData->facebook_url;
        $sparkPostArray['linkedin'] = $affData->linkedin_url;
        $sparkPostArray['google_plus'] = $affData->google_url;
        $sparkPostArray['affiliate_name'] = decryptGdprData($affData->legal_name);
        $sparkPostArray['affiliate_email'] = decryptGdprData($affData->email);
        $sparkPostArray['affiliate_logo'] = url('/uploads/profile_images/' . $affData->logo);
        $sparkPostArray['affiliate_address'] = $affData->company_address;

    if($request->header('serviceId')==1 ){
        $sparkPostArray['provider_name'] = $providerData['elec_data']['provider']->name;
       // $sparkPostArray['provider_number'] = decryptGdprData($providerData['elec_data']['provider']['providerUser']->phone);
        $sparkPostArray['provider_term_conditions'] =""; 
        $sparkPostArray['provider_logo'] = ' ';
        $sparkPostArray['plan_name'] = isset($dataArr['electricity_plan_name']) ?  $dataArr['electricity_plan_name'] : "";
        $sparkPostArray['plan_detail_link'] = isset($dataArr['electricity_plan_detail_link']) ? $dataArr['electricity_plan_detail_link'] : "";

        if($templateType == 4){
            $sparkPostArray['gas_provider_name'] = isset($dataArr['gas_provider_name']) ? $dataArr['gas_provider_name'] : "";
            $sparkPostArray['gas_provider_number'] = isset($dataArr['gas_provider_phone_number']) ? $dataArr['gas_provider_phone_number'] : "";
            $sparkPostArray['gas_provider_term_conditions'] = isset($dataArr['provider_term_conditions']) ? $dataArr['provider_term_conditions'] : "";
            $sparkPostArray['gas_provider_logo'] = ' ';
            $sparkPostArray['gas_plan_name'] = isset($dataArr['gas_plan_name']) ? $dataArr['gas_plan_name'] : "";
            $sparkPostArray['gas_plan_detail_link'] = isset($dataArr['gas_plan_detail_link']) ? $dataArr['gas_plan_detail_link'] : "";
        }
        if($templateType == 3){
            $sparkPostArray['gas_plan_name'] = isset($dataArr['gas_plan_name']) ? $dataArr['gas_plan_name'] : "";
            $sparkPostArray['gas_plan_detail_link'] = isset($dataArr['gas_plan_detail_link']) ? $dataArr['gas_plan_detail_link'] : "";
        }
    }elseif($request->header('serviceId')==3 ){
        $sparkPostArray['provider_name'] = ($providerData['broadband_data']['provider']->name);
       // $sparkPostArray['provider_number'] = decryptGdprData($providerData['broadband_data']['provider']['providerUser']->phone);
        $sparkPostArray['provider_term_conditions'] =""; 
        $sparkPostArray['provider_logo'] = '';
        $sparkPostArray['plan_name'] = isset($providerData['broadband_data']['name']) ?  $providerData['broadband_data']['name'] : "";
       // $sparkPostArray['plan_detail_link'] = $request->plan_url;
        $sparkPostArray['NBN_Key_Fact'] = isset($providerData['broadband_data']['nbn_key_url']) ?  $providerData['broadband_data']['nbn_key_url'] : "";
    }elseif($request->header('serviceId')==2 ){
       
        $sparkPostArray['provider_name'] = ($providerData['mobile_data']['plans']['providers']->name);
        $sparkPostArray['plan_name'] = isset($providerData['mobile_data']['plans']['name']) ?  $providerData['mobile_data']['plans']['name'] : "";
        $sparkPostArray['handset_name'] = isset($providerData['mobile_data']['handset']['name']) ?  $providerData['mobile_data']['handset']['name'] : "";
        $sparkPostArray['variant_name'] = isset($providerData['mobile_data']['variant']['variant_name']) ?  $providerData['mobile_data']['variant']['variant_name'] : "";
        $sparkPostArray['ram'] = isset($providerData['mobile_data']['variant']['capacity']['value']) ?  $providerData['mobile_data']['variant']['capacity']['value'] : "";
        $sparkPostArray['internal_storage'] = isset($providerData['mobile_data']['variant']['internal']['value']) ?  $providerData['mobile_data']['variant']['internal']['value'] : "";
        $sparkPostArray['color'] = isset($providerData['mobile_data']['variant']['color']['title']) ?  $providerData['mobile_data']['variant']['color']['title'] : "";
    }
        return $sparkPostArray;
    }
}
