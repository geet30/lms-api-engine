<?php
namespace App\Traits\Broadband; 
use App\Models\{Services, AffiliateKeys, ConnectionType, PlansBroadband, PlansBroadbandTechnology, AssignedUsers, ProviderLogo, PlansBroadbandAddon, LeadJourneyDataBroadband}; 

use DB;
use Carbon\Carbon;
trait BasicMethod
{   
    /**
     *get a listing of the broadband plans.
     *
     * @return Array
     */
    public static function getPlanList($request)
    {
        try
        {
            $request_array = $request->all();
            $connectionType = $request->input('connection_type');
            $serviceId = $request->header('serviceid');
            $tech_type = $request->input('technology_name');

            $api_key = encryptGdprData($request->header('api-key')); 
            $affiliate_id = AffiliateKeys::where('api_key',$api_key)->pluck('user_id')->first(); 
            $assign_providers = AssignedUsers::where('service_id',$serviceId)->where('source_user_id',$affiliate_id)->where('relation_type',1)->pluck('relational_user_id')->toArray();
            //genrate pl/pd token
            $request_encode_json = json_encode($request_array);
            // $encrypted_remarketing_token = set_encrypt_data($request_encode_json);
            
            // $remarketing_data['remarketing_token'] = $encrypted_remarketing_token;

            $plans = PlansBroadband::where('status','1')->where('connection_type', $connectionType)->with('providers','providers.providerLogo','technologies','contracts','planfees');
            $connections = $technology = ConnectionType::where('status','1')->where('is_deleted','0');
            $connection_name = $connections->where('id',$connectionType)->pluck('name');
            if($tech_type != false){
                $tech_type_id = $technology->where('name','=',$tech_type)->where('status','1')->where('is_deleted','0')->pluck('id');
                if(isset($tech_type_id) && count($tech_type_id) > 0)
                {
                    $plans = $plans->whereHas('technologies', function($q) use($tech_type_id){
                        $q->where('technology_id', $tech_type_id[0]);
                    });
                }
            }
            $plans = $plans->whereHas('providers',function($q) use($assign_providers){
                    return $q->where('status',1)->whereIn('user_id',$assign_providers);
            });

            $plans = $plans->get();
            if(count($plans)>0) {
                $basicPlanDetail = [];
                foreach($plans as $key => $planData)
                {
                    $basicPlanDetail[$key]['plan_id'] = isset($planData->id) ? $planData->id : '';
                    $basicPlanDetail[$key]['plan_name'] = isset($planData->name) ? $planData->name : '';
                    $basicPlanDetail[$key]['plan_nbn_key_url'] = isset($planData->nbn_key_url) ? $planData->nbn_key_url : '';
                    $basicPlanDetail[$key]['inclusion'] = isset($planData->inclusion) ? $planData->inclusion : '';
                    $basicPlanDetail[$key]['cost'] = isset($planData->plan_cost) ? $planData->plan_cost : '';
                        // $basicPlanDetail[$key]['plan_cost_name'] = isset($planData['plan_cost_type'][$key]['cost_name']) ? $planData['plan_cost_type'][$key]['cost_name'] : '';
                    $basicPlanDetail[$key]['cost_description'] = isset($planData->plan_cost_description) ? $planData->plan_cost_description : '';
                    $basicPlanDetail[$key]['special_offer'] = isset($planData->special_offer) ? $planData->special_offer : '';
                    $basicPlanDetail[$key]['special_offer_price'] = isset($planData->special_offer_price) ? $planData->special_offer_price : '';

                        // $basicPlanDetail[$key]['special_cost_name'] = isset($planData['special_cost_type'][$key]['cost_name']) ? $planData['special_cost_type'][$key]['cost_name'] : '';

                    $basicPlanDetail[$key]['special_offer_status'] = isset($planData->special_offer_status) ? $planData->special_offer_status : '';
                    $basicPlanDetail[$key]['internet_speed'] = isset($planData->internet_speed) ? $planData->internet_speed : '';
                    $basicPlanDetail[$key]['satellite_inclusion'] = isset($planData->satellite_inclusion) ? $planData->satellite_inclusion : 'N/A';
                    $basicPlanDetail[$key]['connection_type_info'] = isset($planData->connection_type_info) ? $planData->connection_type_info : '';
                    $basicPlanDetail[$key]['internet_speed_info'] = isset($planData->internet_speed_info) ? $planData->internet_speed_info : '';
                    $basicPlanDetail[$key]['plan_cost_info'] = isset($planData->plan_cost_info) ? $planData->plan_cost_info : '';
                    $basicPlanDetail[$key]['connection_name'] = isset($connection_name[0]) ? $connection_name[0] : '';
                    /*********Plan Description*****/
                        // $basicPlanDetail[$key]['plan_description'] = isset($planData['contents']['description']) ? $planData['contents']['description'] : '';
                    /*********Contract Details****/
                    $basicPlanDetail[$key]['contract_name'] = isset($planData['contracts']['contract_name']) ? $planData['contracts']['contract_name'] : '';
                    $basicPlanDetail[$key]['contact_duration'] = isset($planData['contracts']['validity']) ? $planData['contracts']['validity'] : '';
                    $basicPlanDetail[$key]['contract_description'] = isset($planData['contracts'][' description']) ? $planData['contracts']['description'] : '';
                    /********Provider ************/
                    $basicPlanDetail[$key]['provider_name'] = isset($planData['providers']['name']) ? $planData['providers']['name'] : '';
                    $basicPlanDetail[$key]['provider_id'] = isset($planData->provider_id) ? $planData->provider_id : '';
                   
                    if(isset($planData->provider_id)){
                        if($planData['providers']['providerLogo']){
                            $basicPlanDetail[$key]['provider_logo_name'] = isset($planData['providers']['providerLogo']->name) ? $planData['providers']['providerLogo']->name : '';
                            $basicPlanDetail[$key]['logo_url'] = isset($planData['providers']['providerLogo']->url) ? $planData['providers']['providerLogo']->url : '';  
                        }
                    }
                    /********Plan Data ***/
                    $basicPlanDetail[$key]['total_allowance'] = isset($planData->total_data_allowance) ? $planData->total_data_allowance : '';
                    $basicPlanDetail[$key]['plan_related_data'] = isset($planData->off_peak_data) ? $planData->off_peak_data : '';
                    $basicPlanDetail[$key]['peak_data'] = isset($planData->peak_data) ? $planData->peak_data : '';
                    /********Plan Information ***/                        
                    $basicPlanDetail[$key]['technology'] = isset($request->technology_name) ? $request->technology_name : '';
                    $basicPlanDetail[$key]['download_speed'] = isset($planData->download_speed) ? $planData->download_speed : '';
                    $basicPlanDetail[$key]['upload_speed'] = isset($planData->upload_speed) ? $planData->upload_speed : '';
                    $basicPlanDetail[$key]['speed_description'] = isset($planData->speed_description) ? $planData->speed_description : '';
                    $basicPlanDetail[$key]['typical_peak_time_download_speed'] = isset($planData->typical_peak_time_download_speed) ? $planData->typical_peak_time_download_speed : '';
                    $arrDataLimit = config('plans.data_limit');
                    $basicPlanDetail[$key]['data_limit'] =  isset($planData->data_limit) ? $arrDataLimit[$planData->data_limit] : '';
                    //$basicPlanDetail[$key]['data_limit'] = isset($planData->data_limit) ? $planData->data_limit.' GB' : '';
                    /*********Plan Fees ****/
                    $basicPlanDetail[$key]['plan_fees'] = isset($planData->planfees) ? $planData->planfees->select('fees','fee_id','cost_type_id')->get()->toArray() :'';
                    // $basicPlanDetail[$key]['monthly_cost'] = isset($planData->fees['monthly_cost']) ? $planData->fees['monthly_cost'] :'';
                    // $basicPlanDetail[$key]['minimum_cost'] = isset($planData->fees['minimum_total_cost']) ? $planData->fees['minimum_total_cost'] : '';
                    // $basicPlanDetail[$key]['setup_cost'] = isset($planData->fees['setup_fee']) ? $planData->fees['setup_fee'] :'';
                    // $basicPlanDetail[$key]['delivery_cost'] = isset($planData->fees['delivery_fee']) ? $planData->fees['delivery_fee'] : '';
                    // $basicPlanDetail[$key]['modem_cost'] = isset($planData->fees['modem_cost']) ? $planData->fees['modem_cost'] : '';
                    // $basicPlanDetail[$key]['modem_description'] = isset($planData->fees['modem_description']) ? $planData->fees['modem_description'] : '';
                    // $basicPlanDetail[$key]['processing_fee'] = isset($planData->fees['payment_processing_fees']) ? $planData->fees['payment_processing_fees'] : '';
                    // $basicPlanDetail[$key]['fees_charges'] = isset($planData->fees['other_fee_and_charges']) ? $planData->fees['other_fee_and_charges'] :'';
                            
                }
                if(count($basicPlanDetail) > 0){
                    $planCount = count($basicPlanDetail);
                }else{
                    $planCount = 0;
                }
                // $planRelatedData = [
                //     'plan_count' => $planCount,
                // ];
                
                $finalData = [
                    'plans'=>$basicPlanDetail,
                    'count' =>$planCount,
                ];
            } else {
                $finalData = null;
            }
            if($finalData !="" || $finalData != null){
                $response = ['status' =>true , 'message' => 'success.','response'=>$finalData,'status_code'=>200];
            } else {
                $response = ['status' => false, 'message' => 'Plan not found. Please try again later.','status_code'=>200];
            }
            return $response;
        }
        catch (\Exception $err) {
            throw $err;
        }
    }

    /**
     *get a listing of the broadband plans addons.
     *
     * @return Array
     */
    public static function saveJourneyData($request){
        DB::beginTransaction();
        try{
            $data['lead_id'] = $request['visit_id'];
			$data['connection_type'] = $request['connection_type'];
			$data['technology_type'] = $request['technology_type'];
			$data['address'] = $request['address'];
			$data['movein_type'] = $request['movein_type'];
            if($request['movein_type'] == 1){
                $data['movein_date'] = $request['movein_date'];
            }
			LeadJourneyDataBroadband::updateOrCreate(['lead_id' => $request['visit_id']], $data);
            $response = ['status' =>true , 'message' => 'success.','response'=>'Journey data successfully created.','status_code'=>200];                
            DB::commit();
            return $response;
        }
        catch (\Exception $err) {
            DB::rollback();
            throw $err;
        }            
    }

    /**
     *get a listing of the broadband plans addons.
     *
     * @return Array
     */
    public static function getPlanAddonList($request){
        try{
            $plan_id = $request->input('plan_id');
            $homeConnectionData=[];
            $planAddonsModemData=[];
            $planOtherAddonsData=[];            
            $modemTechId = '';
            $techName = $request->input('technology_name');
            $serviceId = $request->header('serviceid');
            // if(isset($techName) && $techName !='false'){
            //     $findTechName = ConnectionType::where('service_id','=',$serviceId)->where('name','=',$techName)->where('status','1')->where('is_deleted','0')->whereNull('name')->first();
            //     if(isset($findTechName->id)){
            //         $modemTechId = $findTechName->id;
            //     }
            // }
            $data = PlansBroadbandAddon::where('plan_id', $plan_id)->with(['masterAddon'=>function($q){
                $q->with('cost_type');
                $q->where('status','1');
            }])->get();
            foreach($data as $key => $value){
                if(!empty($value->masterAddon['id']) && $value->category == 3){
                    $homeConnectionData[$key]['id'] = isset($value->masterAddon['id']) ? $value->masterAddon['id'] : '';
                    $homeConnectionData[$key]['call_plan_name'] = isset($value->masterAddon['name']) ? $value->masterAddon['name'] :'';
                    $homeConnectionData[$key]['call_plan_inclusion'] = isset($value->masterAddon['inclusion']) ? $value->masterAddon['inclusion'] :'';
                    $homeConnectionData[$key]['call_plan_cost'] = isset($value->masterAddon['cost']) ? $value->masterAddon['cost'] :'';
                    $homeConnectionData[$key]['call_cost_id'] = isset($value->masterAddon->cost_type->id) ? $value->masterAddon->cost_type->id :'';
                    $homeConnectionData[$key]['call_cost_name'] = isset($value->masterAddon->cost_type->cost_name) ? $value->masterAddon->cost_type->cost_name :'';
                    $homeConnectionData[$key]['call_cost_period'] = isset($value->masterAddon->cost_type->cost_period) ? $value->masterAddon->cost_type->cost_period:'';
                    $homeConnectionData[$key]['call_plan_detail'] = isset($value->masterAddon['description']) ? $value->masterAddon['description'] : '';
                    $homeConnectionData[$key]['provider_id'] = isset($value->masterAddon['provider_id']) ? $value->masterAddon['provider_id'] :'';
                    // if(isset($value->masterAddon['provider_id'])){
                    //     $providerId =  $value->masterAddon['provider_id'];
                    //     $providerLogo = ProviderLogo::where('provider_id',$providerId)->first();
                    //     if($providerLogo){
                    //               $homeConnectionData[$key]['provider_logo_name'] = isset($providerLogo->name) ? $providerLogo->name : 'N/A';
                    //               $homeConnectionData[$key]['provider_logo_url'] = isset($providerLogo->url) ? $providerLogo->url : 'N/A';  
                    //     }
                    // }else{
                    //      $homeConnectionData[$key]['provider_logo_name'] = 'N/A';
                    //      $homeConnectionData[$key]['provider_logo_url'] = 'N/A'; 
                    // }
                    $homeConnectionData[$key]['status'] = isset($value->masterAddon['status']) ? $value->masterAddon['status'] :'';
                    $homeConnectionData[$key]['order'] = isset($value->masterAddon['order']) ? $value->masterAddon['order'] :'';
                    $homeConnectionData[$key]['is_default'] = isset($value['is_default']) ? $value['is_default'] :'0';
                    $homeConnectionData[$key]['is_mandatory'] = isset($value['is_mandatory']) ? $value['is_mandatory'] :'0';
                    $homeConnectionData[$key]['show_status'] = isset($value['status']) ? $value['status'] :'0';
                    $homeConnectionData[$key]['show_selected']=0;
                    if($request->has('source') && $request->input('source') == 2){
                        $homeConnectionData[$key]['call_plan_script'] = isset($value->masterAddon['script']) ? $value->masterAddon['script'] :'';
                        if((isset($value['is_default']) && $value['is_default']) == '1' || (isset($value['is_mandatory']) && $value['is_mandatory'] == '1')){
                            $homeConnectionData[$key]['call_plan_script'] = isset($value['script'])? $value['script'] : '';
                        }
                    }
                }
                if(!empty($value->masterAddon['id']) && $value->category == 4){
                    $planAddonsModemData[$key]['id'] = isset($value->masterAddon['id']) ? $value->masterAddon['id'] :'';
                    $planAddonsModemData[$key]['modem_modal_name'] = isset($value->masterAddon['name']) ? $value->masterAddon['name'] :'';
                    $planAddonsModemData[$key]['modem_description'] = isset($value->masterAddon['description']) ? $value->masterAddon['description'] :'';
                    $planAddonsModemData[$key]['price'] = isset($value->cost) ? $value->cost : '';
                    $planAddonsModemData[$key]['broadband_modem_cost_type_id'] = isset($value->cost_type_id) ? $value->cost_type_id : '';
                    $planAddonsModemData[$key]['broadband_modem_cost_name'] = isset($value->masterAddon->cost_type->cost_name) ? $value->masterAddon->cost_type->cost_name : '';
                    $planAddonsModemData[$key]['broadband_modem_cost_period'] = isset($value->masterAddon->cost_type->cost_period) ? $value->masterAddon->cost_type->cost_period : '';
                    $planAddonsModemData[$key]['status'] = isset($value->masterAddon['status']) ? $value->masterAddon['status'] :'';
                    $planAddonsModemData[$key]['order'] = isset($value->masterAddon['order'])? $value->masterAddon['order'] :'';
                    $planAddonsModemData[$key]['is_default'] = isset($value['is_default'])? $value['is_default'] : '0';
                    $planAddonsModemData[$key]['is_mandatory'] = isset($value['is_mandatory'])? $value['is_mandatory'] : '0';
                    if($request->has('source') && $request->input('source') == 2){
                        $planAddonsModemData[$key]['modem_script'] = isset($value['script'])? $value['script'] : '';
                    }
                }
                if(!empty($value->masterAddon['id']) && $value->category == 5){
                    $planOtherAddonsData[$key]['id'] = isset($value->masterAddon['id']) ? $value->masterAddon['id'] :'';
                    $planOtherAddonsData[$key]['addon_name'] = isset($value->masterAddon['name']) ? $value->masterAddon['name'] :'';
                    $planOtherAddonsData[$key]['addon_description'] = isset($value->masterAddon['description']) ? $value->masterAddon['description'] :'';
                    $planOtherAddonsData[$key]['price'] = isset($value->price) ? $value->price :'';
                    $planOtherAddonsData[$key]['addon_cost_id'] = isset($value->cost_type_id) ? $value->cost_type_id :'';
                    $planOtherAddonsData[$key]['addon_cost_name'] = isset($value->masterAddon->cost_type->cost_name) ? $value->masterAddon->cost_type->cost_name :'';
                    $planOtherAddonsData[$key]['addon_cost_period'] = isset($value->masterAddon->cost_type->cost_period) ? $value->masterAddon->cost_type->cost_period :'';
                    $planOtherAddonsData[$key]['status'] = isset($value->masterAddon['status']) ? $value->masterAddon['status'] :'';
                    $planOtherAddonsData[$key]['is_default'] = isset($value['is_default']) ? $value['is_default'] :'0';   
                    $planOtherAddonsData[$key]['show_status'] = isset($value['status']) ? $value['status'] :'0';   
                    $planOtherAddonsData[$key]['is_mandatory'] = isset($value['is_mandatory']) ? $value['is_mandatory'] :'0';
                    if($request->has('source') && $request->input('source') == 2){
                        $planOtherAddonsData[$key]['addon_script'] = isset($value['script'])? $value['script'] : '';
                    }
                }
            }
            $is_boyo_modem = 0;
            $findPlan = PlansBroadband::find($plan_id);
            if($findPlan)
                $is_boyo_modem = isset($findPlan->is_boyo_modem) ? $findPlan->is_boyo_modem :'0' ;
                
            if(!empty($homeConnectionData)){
                foreach ($homeConnectionData as $key => $row){
                    $price[$key] = $row['call_plan_cost'];
                }
                array_multisort($price, SORT_ASC, $homeConnectionData);
            }
            if(!empty($planAddonsModemData)){
                foreach ($planAddonsModemData as $key => $row){
                    $modemPrice[$key] = $row['price'];
                }
                array_multisort($modemPrice, SORT_ASC, $planAddonsModemData);
            }
            if(!empty($planOtherAddonsData)){
                foreach ($planOtherAddonsData as $key => $row){
                    $addonPrice[$key] = $row['price'];
                }
                array_multisort($addonPrice, SORT_ASC, $planOtherAddonsData);
            }

            $finalData = [
                'home_connection'=>array_values($homeConnectionData), 
                'is_boyo_modem'=>$is_boyo_modem,
                'plan_addons_modem'=>array_values($planAddonsModemData),
                'plan_other_addons'=>array_values($planOtherAddonsData),
            ];
            if($finalData){
               $response = ['status' =>true , 'message' => 'success.','response'=>$finalData,'status_code'=>200];
            }else{
              $response = ['status' => false, 'message' => 'not found. Please try again later.','status_code'=>400];
            }
            return $response;
        }
        catch (\Exception $err) {
            throw $err;
        }            
    }

    /**
     *get a listing of the broadband plans addons.
     *
     * @return Array
     */
    public static function deletePlanAddonList($request){
        try{
                          
            $response = ['status' =>true , 'message' => 'success.','response'=>'Addon Data Deleted Successfully','status_code'=>200];                
            return $response;
        }
        catch (\Exception $err) {
            throw $err;
        }            
    }
}


