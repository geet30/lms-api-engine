<?php

namespace App\Repositories\Energy;


use App\Models\{Providers,EnergyPlanRate,Distributor
};
trait GetPlans
{

    static function getElecPlanRateIds($request,$meterType,$allProviders,$distributorArr){
       
        $timeOfUseArray = ['timeofuse_only', 'timeofuse_c1', 'timeofuse_c2', 'timeofuse_c1_c2'];
       
        $planRate = EnergyPlanRate::where('is_deleted', 0)->where('status', 1);
       
        
        if (!empty($request['elec_distributor_id']) && $request['elec_distributor_id'] != 'idontknow') {

            
            $planRate->where(function ($inner_query) use ($distributorArr, $allProviders) {
                $inner_query->where('distributor_id', $distributorArr);
                $inner_query->whereIn('provider_id', $allProviders);
            });
            } else {
                $planRate->where('distributor_id', null);
            }
            
            if (isset($request['electricity_tariff_type_code']) && !empty($request['electricity_tariff_type_code'])) {
                $planRate->where('tariff_type_code', $request['electricity_tariff_type_code']);
            }
            
            if (isset($request['meter_type_code']) && !empty($request['meter_type_code']) && !isset($request['electricity_tariff_type_code'])) {
                $planRate->where('tariff_type_code', $request['meter_type_code']);
            }
            
            if (in_array($meterType, $timeOfUseArray)) {
                //check if meter type sent is TOU and shoulder_time_of_use is not empty
                if (isset($request['shoulder_timeofuse_usage']) && $request['shoulder_timeofuse_usage'] != '') {
                    //selecting flixible
                    $planRate->where('time_of_use_rate_type', '1');
                } else {
                    //selecting Normal Rate
                    $planRate->where('time_of_use_rate_type', '2');
                }
            }
            
            $planRateIds =  $planRate->where('type',$meterType)->pluck('plan_id');
           
            return $planRateIds;
        
        
    }
    static function getElecPlans($request,$meterType,$allProviders,$planRateIds,$distributorArr,$providerSettingData){

        //check resale allow if not than remove provider
        foreach($providerSettingData as $key=>$provider){
            if($provider['is_retention'] == 0)
                if($provider['user_id'] == $request->electricity_provider){
                    unset($providerSettingData[$key]);
                    $keyarr = array_search($provider['user_id'], $allProviders->toArray());
                    unset($allProviders[$keyarr]);
                }
                
        }
        $plans = self::whereIn('id', $planRateIds)->whereIn('provider_id', $allProviders)->with(
            ['rate'=> function($q)use($distributorArr, $meterType, $request){
                $q->where('distributor_id', $distributorArr)->where('type',$meterType)->with(['planRateLimit']);
                if($request['demand'] == 1){
                    $q->with('tariffInfo.tariffRates');
                }
            },'planSolarRate',
            'planSolarRateNormal',
            'planSolarRatePermimum',
            'getPlanTags' => function ($q) {

                $q->with(['tags' => function ($query) {
                    $query->where('status', 1)->where('is_deleted', 0);
                }]);
            }])
            ->where('energy_type',1)
            ->where('plan_type', $request['property_type'])
            ->where('view_discount', '!=', '')
            ->where('view_contract', '!=', '')
            ->where('view_benefit', '!=', '')
            ->where('view_exit_fee', '!=', '')
            //->where('is_deleted', 0)
            ->where('status', 1);
            if(isset($request['solar_panel'])&& $request['solar_panel'] == 1){
                $plans = $plans->where('solar_compatible',1);
            }
            if (isset($request['plan_limit']) && !empty($request['plan_limit'])) {
                $plans = $plans->take($request['plan_limit'])->get()->toArray();
            } else {
                $plans = $plans->get()->toArray();
            }
            $data['plans'] = $plans;
            $data['providers'] = $providerSettingData;
            return $data;
        }

        static function getGasPlans($request,$meterType,$allProviders,$planRateIds,$distributorArr,$providerSettingData){
            //check resale allow if not than remove provider
            
        foreach($providerSettingData as $key=>$provider){
            if($provider['is_retention'] == 0)
                if($provider['user_id'] == $request->gas_provider){
                    unset($providerSettingData[$key]);
                    $keyarr = array_search($provider['user_id'], $allProviders->toArray());
                    unset($allProviders[$keyarr]);
                }
        }
            
            $plans = self::whereIn('id', $planRateIds)->whereIn('provider_id', $allProviders)->with(
                ['rate'=> function($q)use($distributorArr, $meterType){
                    $q->where('distributor_id', $distributorArr)->where('type',$meterType)->with('planRateLimit');
                }, 
                'getPlanTags' => function ($q) {
                    $q->with(['tags' => function ($query) {
                        $query->where('status', 1)->where('is_deleted', 0);
                    }]);
                },])
               
                ->where('energy_type',2)
                ->where('plan_type', $request['property_type'])
                ->where('view_discount', '!=', '')
                ->where('view_contract', '!=', '')
                ->where('view_benefit', '!=', '')
                ->where('view_exit_fee', '!=', '')
                //->where('is_deleted', 0)
                ->where('status', 1);
                if (isset($request['plan_limit']) && !empty($request['plan_limit'])) {
                    $plans = $plans->take($request['plan_limit'])->get()->toArray();
                } else {
                    $plans = $plans->get()->toArray();
                }
                
                $data['plans'] = $plans;
                $data['providers'] = $providerSettingData;
                return $data;

        }

        static function getGasPlanRateIds($request,$meterType,$allProviders){
            
            
            $planRate = EnergyPlanRate::where('is_deleted', 0)->where('status', 1);

           
            if (!empty($request['gas_distributor_id']) && $request['gas_distributor_id'] != 'idontknow') {
    
               
                $planRate->where(function ($inner_query) use ($request, $allProviders) {
                    $inner_query->where('distributor_id', $request['gas_distributor_id']);
                    $inner_query->whereIn('provider_id', $allProviders);
                });
                } else {
                    $planRate->where('distributor_id', null);
                }
               
                if (isset($request['gas_tariff_type_code']) && !empty($request['gas_tariff_type_code'])) {
                    $planRate->where('tariff_type_code', $request['gas_tariff_type_code']);
                }
                
                $planRateIds =  $planRate->where('type',$meterType)->pluck('plan_id');
               
              
                return $planRateIds;
            
            
        }
        
    /**
     * @param array $input
     *
     * @throws GeneralException // @phpstan-ignore-line
     *
     * @return bool
     */

   static function  getElecPlanDeatils($requestData){
      
       $PlanId =$requestData['electricity_plan_id'];
       $planRateId = $requestData['elec_plan_rate_id'];
      $selectData = self::where('id', $PlanId)->with(['rate'=>function($q)use($planRateId){
        $q->with('planRateLimit');
        if ($planRateId) {
            $q->where('id', $planRateId);
           
            $q->with(['tariffInfo' => function ($rateQuery) {
                $rateQuery->select('id', 'plan_rate_ref_id', 'tariff_code_ref_id', 'tariff_discount', 'tariff_daily_supply', 'tariff_supply_discount', 'status', 'daily_supply_charges_description', 'discount_on_usage_description', 'discount_on_supply_description');
                $rateQuery->with('tariffRates');
                }]);
        }
      },'planSolarRate','provider'=> function($q){
            $q->with(['logo'=> function($logo){
                $logo->where('category_id',9)->where('status',1)->select('user_id','name','url');
            },'EnergyProviderContent'=>function($content){
                $content->where('type','11')->select('provider_id','why_us','description','status','type');
            }]);
        }])->first();

        $distributorData = Distributor::where('id',$requestData->elec_distributor_id)->select('id','name')->first();

        $planResult = collect($selectData);
        $planResult->put('distributorData', $distributorData);

        return $planResult;

    }
    static function  getGasPlanDeatils($requestData){
        $PlanId =$requestData['gas_plan_id'];
        $planRateId = $requestData['gas_plan_rate_id'];
        $selectData = self::where('id', $PlanId)->with(['rate'=>function($q)use($planRateId){
         $q->with('planRateLimit');
         if ($planRateId) {
             $q->where('id', $planRateId);
         }
       },'provider'=> function($q){
            $q->with(['logo'=> function($logo){
                $logo->where('category_id',9)->where('status',1)->select('user_id','name','url');
            },'EnergyProviderContent'=>function($content){
                $content->where('type','11')->select('provider_id','why_us','description','status','type');
            }]);
        }])->first();
        $distributorData = Distributor::where('id',$requestData->gas_distributor_id)->select('id','name')->first();
        $planResult = collect($selectData);
        $planResult->put('distributorData', $distributorData);
        return $planResult;
 
    }
}


