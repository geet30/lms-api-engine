<?php

namespace App\Traits\Provider;
use Illuminate\Support\Facades\Auth;
use App\Models\{ Provider,PlanEnergy,AppSetting,Lead, Visitor};
trait ApplyNow
{
    
    /**
     * Author:Geetanjali (19-March-2022)
     * Get Services or Single Service.
     * @param string  planId
     * @return object keyData
    */
    static function getApplyNowContent($request) {
        $data = [];
        $provider_ids =  [];
        $energy_type  =0;
        $service_id = $request->header('ServiceId');
        if($request->provider_id && $request->gas_provider_id){
            $provider_ids =  [$request->provider_id, $request->gas_provider_id];
            $energy_type = 3; // both gas and electricity
        }

        else if($request->provider_id){
            $provider_ids =  [$request->provider_id];
            $energy_type = 1; // only electricity
        }
        else if($request->gas_provider_id){
            $provider_ids =  [$request->gas_provider_id];
            $energy_type = 2; // only gas
        }
        $finalResponseData = self::getApplyContent($request);

        $data =  $finalResponseData;
        $data['getproviderContent'] = self::getproviderContent($energy_type, $provider_ids,$service_id);
     
        
        return $data;
   
       
    }
    static function getApplyContent($request){
        $apply_now_attributes = AppSetting::where('type', '=', 'apply_now_attributes')->get()->first()->toArray();
		$apply_now_attributes = explode(',', $apply_now_attributes['attributes']);
        $finalResponseData =[];
        $plan_id = [];
        if(isset($request->electricity_plan_id) && !empty($request->electricity_plan_id) && isset($request->gas_plan_id) && !empty($request->gas_plan_id)){
            $plan_id =  [$request->electricity_plan_id, $request->gas_plan_id];
            $res_array = [ 0 => 'electricity' ,1=>'gas'];
        }
        else if (isset($request->electricity_plan_id) && !empty($request->electricity_plan_id)) {
            $plan_id =  [$request->electricity_plan_id];
            $res_array = [ 0 => 'electricity' ];
        }
        else if (isset($request->gas_plan_id) && !empty($request->gas_plan_id)) {
            $plan_id =  [$request->gas_plan_id];
            $res_array = [ 0 => 'electricity' ];

        }
        if (isset($plan_id) && !empty($plan_id)) {
            
            $content = PlanEnergy::where('status', 1)->whereIn('id', $plan_id)->select('apply_now_content',  'name', 'apply_now_status', 'provider_id','energy_type')->get();

          
            foreach ($content as $key=>$data) {
                if ($data) {
                   
                    if($data->provider){
                        $parameters = self::get_parameter_replacement($data, $data->provider);
                        if (!empty($data->apply_now_content) && $data->apply_now_status == 1) {
                            $plan_content = true;
                            $apply_content = str_replace($apply_now_attributes, $parameters, $data->apply_now_content);
                        } else {
                            $plan_content = false;

                            if (!empty($data->provider->apply_pop_content) && $data->provider->apply_now_pop_status == 'yes') {
                                $apply_content = str_replace($apply_now_attributes, $parameters, $content->provider->apply_pop_content);
                            }
                        }
                        $finalResponseData[$res_array[$key]]['selected'] = true;
                        $finalResponseData[$res_array[$key]]['providerId'] = $data['provider_id'];
                        $finalResponseData[$res_array[$key]]['apply_content'] = null;
                        $finalResponseData[$res_array[$key]]['plan_content'] = null; 
                        $finalResponseData[$res_array[$key]]['Provider_logo'] =$data->provider->logo;
                        $finalResponseData[$res_array[$key]]['PlanName'] =  $data['name'];

                        if (!empty($apply_content)) {
                            $finalResponseData[$res_array[$key]]['apply_content'] = $apply_content;
                            $finalResponseData[$res_array[$key]]['plan_content'] = $plan_content;
                        }
                        
                    }
                }
            }
        }
        return $finalResponseData;
    }
    static function get_parameter_replacement($plan, $provider)
	{

		$params['provider_name'] = $provider->legal_name;
		$params['provider_term_and_condition'] = 'Provider_Term_And_Conditions';
		$params['provider_logo'] = "<img src='" . $provider->logo . "' width='120' alt='".$provider->legal_name."'/>";
		$params['plan_name'] = $plan->name;
		$params['provider_phone_number'] = $provider->phone;		
		$params['provider_email'] = $provider->email;

		return $params;
	}
    static function getproviderContent($type,$provider_ids,$service_id){
        $provider = Provider::whereIn('user_id', $provider_ids)->where('is_deleted', '0')->with([
            'user' => function ($query) {
                $query->select('id','phone','email');
            },
            'logo' => function ($query) {
                $query->where('category_id', 9)->first();
            },
            'provider_content'=>function ($query2) {
                $query2->where('type', '12')->where('status',1)->select('provider_id','description','show_plan_on','id')->first();
            }
            ]
        )->get()->toArray();
     
        $leadId = decryptGdprData(request()->visit_id);
        $visitor = Lead::getFirstLead(['lead_id'=>$leadId], ['first_name','middle_name','last_name','phone','email'], true);
        $user = Auth::user();
        $affilateData = $user->getAffiliate(['legal_name','logo','dedicated_page']);
        
        if ($visitor) {
            $visitor = Visitor::removeGDPR($visitor);
        }
        
        $attributes = [
            '@Provider_Name@',
            '@Provider_Term_And_Conditions@',
            '@Provider_Logo@',
            '@Provider_Phone_Number@',
            '@Plan_Name@',
            // '@Price_Fact_Sheet_Link@',
            '@Provider_Email@',
            '@Affiliate_Name@',
            '@Affiliate_Logo@'
        ];
        if ($visitor) {
            array_push($attributes, '@name@');
            array_push($attributes, '@Customer_Full_Name@');
            array_push($attributes, '@Customer_Mobile_Number@');
            array_push($attributes, '@Customer_Email@');
        }
  
        $nextParameter = $showPlanOn = [];
        $ImageURL = null;
        $data = [];
        if (isset($provider)) { 
            $dataarr = [];

            foreach($provider as $key=>$providerInfo){
                if(isset($providerInfo['logo'][0]))
                { 
                    $ImageURL = $providerInfo['logo'][0]['url'];
                }
                if(isset($providerInfo['provider_content'][0]))
                { 
                    $data = $providerInfo['provider_content'][0]['description'];
                    $showPlanOn =  json_decode($providerInfo['provider_content'][0]['show_plan_on'], true)??[];
                }
   
                $nextParameter['Provider_Name'] =($providerInfo['name'] && !empty($providerInfo['name'])) ?$providerInfo['name']: $providerInfo['name'];
                $nextParameter['Provider_Term_And_Conditions'] = isset($nextParameter['Provider_Name'])?$affilateData->dedicated_page.'/provider-term-conditions/?provider='.$nextParameter['Provider_Name']:'';
                $imgSrc = '';
                if ($ImageURL) {
                    $imgSrc = "<img alt='".$nextParameter['Provider_Name']."' src='".$ImageURL."'height= 30 width=30>";
                }
                $nextParameter['Provider_Logo'] = $imgSrc;
                
                $nextParameter['Provider_Phone_Number'] = isset($providerInfo['user']['phone']) ?decryptGdprData($providerInfo['user']['phone']):'';
                $nextParameter['Plan_Name'] = '';
                // $nextParameter['Price_Fact_Sheet_Link'] = 'Price_Fact_Sheet_Link';
                $nextParameter['Provider_Email'] = isset($providerInfo['user']['email']) ?decryptGdprData($providerInfo['user']['email']):'';
                $nextParameter['Affiliate_Name'] = decryptGdprData($affilateData->legal_name) ?? '';
                $s3fileName =   str_replace("<aff-id>", $user->id, config('env.AFFILIATE_LOGO'));
                $affLogo = config('env.Public_BUCKET_ORIGIN') . config('env.DEV_FOLDER') . $s3fileName . $affilateData->logo;
                if ($affLogo) {
                    $affLogo = "<img alt='".$nextParameter['Affiliate_Name']."' src='".$affLogo."'height= 40 width=40>";
                }
                $nextParameter['Affiliate_Logo'] = $affilateData->logo?$affLogo:'';

                if ($visitor) {
                    $fullName = $visitor->first_name.' '.$visitor->middle_name. ' '. $visitor->last_name;
                    $nextParameter['name'] = $visitor->first_name ?? '';
                    $nextParameter['Customer_Full_Name'] = trim($fullName)??'';
                    $nextParameter['Customer_Mobile_Number'] = $visitor->phone ?? '';
                    $nextParameter['Customer_Email'] = $visitor->email??'';
                }
                
                $data = str_replace($attributes, $nextParameter, $data);
                $dataarr['provider_EIC'] = '';
                $data = in_array(1, $showPlanOn)?$data:'';

                if($type == 3){
                    
                    if($key==0){
                        $dataarr['provider_EIC'] =   $data;
                    }
                    if($key == 1){
                        
                        $dataarr['gasProviderEIC'] =  $data;
                    }
                }
                else if($type == 2){
                
                
                    $dataarr['gasProviderEIC'] =   $data;
                }
                else if($type == 1){
                    $dataarr['provider_EIC'] =   $data;
                }
            }

            return $dataarr;
            
        }
    }
}
?>