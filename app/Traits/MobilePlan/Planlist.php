<?php

namespace App\Traits\MobilePlan;

use App\Models\{
    PlanMobile,
    AffiliateKeys,
    Provider,
    Variant,
    PlanHandset,
    Handset,
    PlanVariant,
    ProviderLogo,
    PlanContract,
    InternalStorage,
    PlanTelcoContents,
    Color,
    ConnectionType,
    AssignedUsers,
    
};
use Illuminate\Support\Facades\Auth;
use PhpParser\Node\Stmt\TryCatch;
use DB;

trait Planlist
{


    /**
     * Author:Geetanjali (12-March-2022)
     * Get Services or Single Service.
     
     */
    public static function getPlanList($request)
    {

        try {
            $plans = [];
            $plan_options = [];
            $disAllowPlan = [];
            $serviceId = $request->header('ServiceId');
            $plan_options = self::fetchPlanSelectionMethod($request->all(), $serviceId);

            $api_key = encryptGdprData($request->header('api-key'));
            $affiliateId =  Auth::user()->id;
           
            $assignProviders = AssignedUsers::where('source_user_id', $affiliateId)
                ->where('relation_type', 1)
                ->where('status', 1)
                ->whereHas('providers')
                ->with('providers')
                ->where('service_id', Provider::SERVICE_MOBILE)
                ->pluck('relational_user_id')->toArray();


            if (isset($plan_options['providers_filter']) && $plan_options['providers_filter']) {
                $assignProviders = array_values(array_intersect($assignProviders, $plan_options['providers_filter']));
            }
           
            if (!count($assignProviders)) {
                return false;
            }
            
             $disallowedPlan = DB::table('provider_disallow_plans')->where('affiliate_id',$affiliateId)->select('plan_id')->get();
            
            foreach($disallowedPlan as $pln){
                 $disAllowPlan[] = $pln->plan_id;
             }
            // check if request has plan_type sim and connection type is personal
            switch ($plan_options['plan_type']) {
                case ConnectionType::PLAN_TYPE_SIM:


                    $plans['sim'] = self::fetchSimOnlyPlans($plan_options, $assignProviders,$disAllowPlan);
                    if (isset($plans['sim']['plan_result']) && !empty($plans['sim']['plan_result'])) {
                        return $plans['sim'];
                    } else {
                        return false;
                    }
                    break;
                case ConnectionType::PLAN_TYPE_MOBILE:
                    $plans['both'] = self::fetchSimHandsetPlans($plan_options, $assignProviders,$disAllowPlan);

                    if (isset($plans['both']['plan_result']) && !empty($plans['both']['plan_result'])) {
                        return $plans['both'];
                    } else {
                        return false;
                    }

                    break;
                default:
                    break;
            }
            return $plans;
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Something went wrong. Please try again Later.' . $e->getMessage(), 'status_code' => 400];
        }
    }

    public static function fetchPlanSelectionMethod($inputs, $serviceId)
    {

        $connection_type = isset($inputs['connection_type']) ? $inputs['connection_type'] : 1;
        $serviceId = isset($serviceId) ? $serviceId : config('app.mobile_service_id');

        $plan_type = isset($inputs['plan_type']) ? $inputs['plan_type'] : 1;
        $sortMethod = isset($inputs['sortMethod']) ? $inputs['sortMethod'] : 'cost';
        $sortBy = isset($inputs['sortBy']) ? $inputs['sortBy'] : 'ASC';
        $data_usage_min = isset($inputs['data_usage_min']) ? $inputs['data_usage_min'] : 0;
        $plan_cost_min = isset($inputs['plan_cost_min']) ? $inputs['plan_cost_min'] : 0;
        $plan_cost_max = isset($inputs['plan_cost_max']) ? $inputs['plan_cost_max'] : 40000;
        $max_range = isset($inputs['max_range']) ? $inputs['max_range'] : 40000;
        $current_provider = isset($inputs['current_provider']) ? $inputs['current_provider'] : '';
        $current_provider_id = '';

        // if($current_provider){

        //     $provider_list_name=ConnectionType::where('service_id',ConnectionType::SERVICE_MOBILE)->where('connection_type_id',ConnectionType::CONNECTION_TYPE_ID_THREE)->value('name');       
        //     $current_provider_id = Provider::where('name', $provider_list_name)->value('user_id');
        // }
        // 
        $variants_id = [];
        $own_lease = isset($inputs['own_lease_filter']) ? $inputs['own_lease_filter'] : [1, 2];
        $providers_filter = isset($inputs['providers_filter']) ? $inputs['providers_filter'] : [];
        $storage = isset($inputs['storage']) ? $inputs['storage'] : [];
        $contract_filter = isset($inputs['contract_filter']) ? $inputs['contract_filter'] : [];
        $handsetIds = isset($inputs['handset_filter']) ? $inputs['handset_filter'] : [];
        $simType = isset($inputs['sim_type']) ? $inputs['sim_type'] : '';
        $simvariantId  = isset($inputs['variant_filter']) ? $inputs['variant_filter'] : [];

        // if(isset($inputs['handset_id']) && !empty($inputs['handset_id'])){
        //     foreach($inputs['handset_id'] as $variant_data){

        //         $variants_id[] = isset($variant_data) ? $variant_data : [];

        //     }
        // }


        return [
            'connection_type' => $connection_type,
            'service_id' => $serviceId,
            'own_lease' => $own_lease,
            'plan_type' => $plan_type,
            'sortMethod' => $sortMethod,
            'sortBy' => $sortBy,
            'data_usage_min' => $data_usage_min,
            'plan_cost_min' => $plan_cost_min,
            'plan_cost_max' => $plan_cost_max,
            'max_range' => $max_range,
            'providers_filter' => $providers_filter,
            'current_provider' => $current_provider,
            'current_provider_id' => $current_provider_id,
            'variants_id' => $variants_id,
            'handset_id' => $handsetIds,
            'storage' => $storage,
            'contract_filter' => $contract_filter,
            'sim_type_filter' => $simType,
            "variant_id" => $simvariantId
        ];
    }


    public static function fetchSimHandsetPlans($plan_options, $assignProviders,$disallowedPlan)
    {
        $plan = [];

        date_default_timezone_set('Australia/Sydney');
        $contracts = isset($plan_options['contract_filter']) ? $plan_options['contract_filter'] : null;
        $handsetIds = isset($plan_options['handset_id']) ? $plan_options['handset_id'] : null;
        $simType = isset($plan_options['sim_type_filter']) ? $plan_options['sim_type_filter'] : null;

        if (empty($handsetIds)) {
            $handsetIds =  Handset::where('status', 1)->pluck('id');
        }

        $plans = PlanMobile::whereHas('providers', function ($q) use ($assignProviders) {
            $q->whereIn('user_id', $assignProviders);
        })
            ->when($contracts, function ($q) use ($contracts) {
                $q->whereIn('contract_id', $contracts);
            })
            // ->when($simType, function($q) use($simType) {      

            //     if($simType !=3){
            //         $q->where('sim_type',$simType);
            //         $q->orWhere('sim_type',3);
            //     }
            //  })
            ->whereNotIn('id',$disallowedPlan)
            ->where('connection_type', $plan_options['connection_type'])
            ->where('plan_type', $plan_options['plan_type'])
            ->whereBetween('cost', [$plan_options['plan_cost_min'], $plan_options['plan_cost_max']])
            ->where('plan_data', '>=', $plan_options['data_usage_min'])
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->where('activation_date_time', '<=', date('Y-m-d H:i:s'))
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->where('deactivation_date_time', '=', '')
                        ->orWhereNull('deactivation_date_time');
                })->orWhere('deactivation_date_time', '>=', date('Y-m-d H:i:s'));
            })
            ->with([
                'contract' => function ($q) {
                    $q->select('id', 'validity','contract_name')->where('status',1);
                }, 'providers' => function ($q) {
                    $q->select('user_id', 'name');
                }, 'providers.PlanListLogo' => function ($q) {
                    $q->select('name', 'user_id', 'id');
                }, 'cost_type' => function ($q) {
                    $q->select('cost_name', 'cost_period', 'order', 'id');
                },
                //'planHandsets'=>function($pHand)use($handsetIds){
                //  $pHand->whereIn('handset_id',$handsetIds)->with('handset');
                //},
                'PlanVariant' => function ($pVarient) use ($handsetIds, $plan_options) {
                    $pVarient->whereIn('variant_id', $plan_options['variant_id'])->whereIn('handset_id', $handsetIds);
                    $pVarient->where('status',1);
                    if (in_array(1, $plan_options['own_lease']) && in_array(2, $plan_options['own_lease'])) {

                        // $pVarient->where('own', 1);
                        // $pVarient->orwhere('lease', 1);
                    } else {
                        if (in_array(1, $plan_options['own_lease'])) {
                            $pVarient->where('own', 1);
                        }
                        if (in_array(2, $plan_options['own_lease'])) {

                            $pVarient->where('lease', 1);
                        }
                    }
                    //$pVarient->whereIn('handset_id',$handsetIds);

                    $pVarient->with(['PlanContracts' => function ($qu) use ($plan_options) {
                        if (count($plan_options['contract_filter'])) {
                            $qu->whereIn('contract_id', $plan_options['contract_filter']);
                            $qu->where('status',1);
                        }
                        $qu->with('contract');
                    }, 'variant' => function ($q) use ($plan_options) {
                        $q->with('color', 'capacity', 'internal', 'images');
                        // if(count($plan_options['storage'])){
                        //     $q->whereHas('storage')->with(['storage'=>function($data) use ($plan_options){
                        //         $data->whereIn('id',$plan_options['storage']);
                        //     }]);
                        // }else{
                        //     $q->whereIn('id',$plan_options['storage']);
                        // }
                    }, 'handset']);
                }
            ])
            ->select('id', 'provider_id', 'name', 'plan_type', 'connection_type', 'inclusion', 'plan_data', 'plan_data_unit', 'contract_id', 'cost', 'cost_type_id', 'sim_type', 'host_type','network_type')->get();
       // dd($plans->toArray());
        //    self::calculatePlanCost($plans);
        $hostNames =  ConnectionType::where('connection_type_id',4)->where('service_id',2)->select('local_id','name')->get()->toArray();
        $plans =  self::setPlanListingResponse($plans, $plan_options,$hostNames);
        $plans =  self::handsetPlanSorting($plans,$plan_options);
        return ['plan_result' => $plans];




        //     ->when($contracts, function($q) use($contracts) {
        //        $q->whereIn('contract_id', $contracts);
        //     })
        //     ->where('connection_type', $plan_options['connection_type']) 
        //     ->where('plan_type', $plan_options['plan_type']) 
        //     ->whereBetween('cost', [$plan_options['plan_cost_min'], $plan_options['plan_cost_max']])
        //     ->where('plan_data' ,'>=', $plan_options['data_usage_min'])
        //     ->where('status', 1)
        //     ->whereNull('deleted_at')
        //     ->where('activation_date_time', '<=', date('Y-m-d H:i:s'))
        //     ->where(function($q){
        //            $q->where(function($qq)
        //                {
        //                    $qq->where('deactivation_date_time','=','')
        //                    ->orWhereNull('deactivation_date_time');
        //                })->orWhere('deactivation_date_time','>=', date('Y-m-d H:i:s'));
        //    })
        //    ->with([
        //     'contract'=>function($q) {
        //         $q->select('id','validity');
        //     },'providers'=>function($q){
        //         $q->select('user_id','name');
        //     },'providers.logo'=>function($q){
        //         $q->select('url','user_id','id');
        //     },'cost_type'=>function($q){
        //         $q->select('cost_name','cost_period','order','id');
        //     }
        // ])
        // ->select('id','provider_id','name','plan_type','connection_type','inclusion','plan_data','plan_data_unit','contract_id','cost','cost_type_id')->get();

        //  $plans = self::fetchSimHandsetConnections($plan_options, $plan,ConnectionType::PLAN_TYPE_MOBILE);
        // return $plans;
    }

    public static function fetchSimOnlyPlans($plan_options, $assignProviders,$disallowedPlan)
    { // plan type = 1

        try {
            
            date_default_timezone_set('Australia/Sydney');
            $contracts = isset($plan_options['contract_filter']) ? $plan_options['contract_filter'] : null;
            $simType = isset($plan_options['sim_type_filter']) ? $plan_options['sim_type_filter'] : null;

            $plans = PlanMobile::whereHas('providers', function ($q) use ($assignProviders) {
                $q->whereIn('user_id', $assignProviders);
            })
                ->when($contracts, function ($q) use ($contracts) {
                    $q->whereIn('contract_id', $contracts);
                })
                // ->when($simType, function($q) use($simType) {
                //     if($simType !=3){
                //         if($simType == 1){
                //             $q->where('sim_type','!=',2); 
                //         }elseif($simType == 2){
                //             $q->where('sim_type','!=',1); 
                //         }
                //     }
                //  })
                ->whereNotIn('id',$disallowedPlan)
                ->where('connection_type', $plan_options['connection_type'])
                ->where('plan_type', $plan_options['plan_type'])
                ->whereBetween('cost', [$plan_options['plan_cost_min'], $plan_options['plan_cost_max']])
                ->where('plan_data', '>=', $plan_options['data_usage_min'])
                ->where('status', 1)
                ->whereNull('deleted_at')
                ->where('activation_date_time', '<=', date('Y-m-d H:i:s'))
                ->where(function ($q) {
                    $q->where(function ($qq) {
                        $qq->where('deactivation_date_time', '=', '')
                            ->orWhereNull('deactivation_date_time');
                    })->orWhere('deactivation_date_time', '>=', date('Y-m-d H:i:s'));
                })->with('PlanHostType')
                ->with([
                    
                    'contract' => function ($q) {
                        $q->select('id', 'validity','contract_name');
                    }, 'providers' => function ($q) {
                        $q->select('user_id', 'name');
                    }, 'providers.PlanListLogo' => function ($q) {
                        $q->select('name', 'user_id', 'id');
                    }, 'cost_type' => function ($q) {
                        $q->select('cost_name', 'cost_period', 'order', 'id');
                    },
                ])
                ->select('id', 'provider_id', 'name', 'plan_type', 'connection_type', 'inclusion', 'plan_data', 'plan_data_unit', 'contract_id', 'cost', 'cost_type_id', 'sim_type', 'host_type','network_type','special_offer_title','special_offer_cost','special_offer_description','special_offer_status','network_host_information')->get();
                $plans= self::calculateSimCost($plans);
                usort($plans, function ($a, $b) {
                    return $a['cost'] > $b['cost'];
                });
            return ['plan_result' => $plans];
        } catch (\Throwable $th) {
            return $th->getMessage(); //throw $th;
        }
    }

    static function fetchSimHandsetConnections($plan_options, $plan, $plan_type)
    {
        $message = '';

        $connection_type = $plan_options['connection_type'];
        $plan = $plan->where('connection_type', $connection_type);


        if (ConnectionType::CONNECTION_TYPE_ONE == $connection_type && $plan_type ==  ConnectionType::PLAN_TYPE_MOBILE) { //personal connection type

            $plan = self::simHandsetPlanFilter($plan_options, $plan);
            $message = 'personal case';
        } else if (ConnectionType::CONNECTION_TYPE_TWO == $connection_type && $plan_type == ConnectionType::PLAN_TYPE_MOBILE) {
            $plan = self::simHandsetPlanFilter($plan_options, $plan);
            $message = 'business case';
        } else if (ConnectionType::CONNECTION_TYPE_THREE == $connection_type  && $plan_type == ConnectionType::PLAN_TYPE_MOBILE) {
            $plan = $plan->where('business_size', 2)->where('bdm_available', 0);
            $plan = self::simHandsetPlanFilter($plan_options, $plan);
            $message = 'business case';
        } else {
            $message = 'default case';
        }
        $plans = ['plan_result' => $plan, 'html' => '', 'message' => $message];
        return $plans;
    }

    public static function fetchConnections($plan_options, $plan, $plan_type)
    {
        $message = '';
        $connection_type = $plan_options['connection_type'];
        // $plan = $plan->where('connection_type', $connection_type);


        if (ConnectionType::CONNECTION_TYPE_ONE == $connection_type && $plan_type ==  ConnectionType::PLAN_TYPE_SIM) { //personal connection type

            $plan = self::simPlanFilter($plan_options, $plan);
            $message = 'personal case';
        } else if (ConnectionType::CONNECTION_TYPE_TWO == $connection_type && $plan_type ==  ConnectionType::PLAN_TYPE_SIM) {
            $plan = self::simPlanFilter($plan_options, $plan);
            $message = 'business case';
        } else if (ConnectionType::CONNECTION_TYPE_THREE == $connection_type  && $plan_type ==  ConnectionType::PLAN_TYPE_SIM) {
            $plan = $plan->where('business_size', 2)->where('bdm_available', 0);
            $plan = self::simPlanFilter($plan_options, $plan);
            $message = 'business case';
        } else {
            $message = 'default case';
        }
        // $plans = ['plan_result'=>$plan, 'html'=>'','message'=>$message,'count'=>count($plan)];
        $plans = ['plan_result' => $plan, 'html' => '', 'message' => $message];
        return $plans;
    }


    static function simHandsetPlanFilter($plan_options, $plan)
    {

        $plan = self::fetchPlansBasedOnNormalFilter($plan, $plan_options);
        $plan = $plan->pluck('id');
        $result = [];
        $result = self::fetchAllVariantsWithPlan($plan, $plan_options);

        if ($result) {
            $result = self::handsetPlanSorting($result, $plan_options);
            $result = self::arrayUniqueByKey($result, "combine_ids_for_unique_array");
        }

        return  $result;
    }
    static function simPlanFilter($plan_options, $plan)
    {
        $plan = self::simPlanContractFilter($plan_options, $plan);
        $plan = self::fetchPlansBasedOnNormalFilter($plan, $plan_options);
        $plan = $plan->get();
        if (isset($plan_options['plan_cost_min']) && isset($plan_options['plan_cost_max'])) {
            $plan = self::fetchSimPlanBasedOnCost($plan, $plan_options);
        }

        $plan = self::simPlanSorting($plan_options, $plan);
        return $plan;
    }
    static function handsetPlanSorting($result, $plan_options)
    {
        if (strtolower($plan_options['sortBy']) == 'asc') {
            array_multisort(array_column($result, 'total_cost'), SORT_ASC, $result);
        } else {
            array_multisort(array_column($result, 'total_cost'), SORT_DESC, $result);
        }
        return $result;
    }
    /**
     * return unique plan name according plan,storage,color,capacity.
     **/
    public static  function arrayUniqueByKey(&$array, $key)
    {
        $tmp = array();
        $result = array();
        foreach ($array as $value) {
            if (!in_array($value[$key], $tmp)) {
                array_push($tmp, $value[$key]);
                array_push($result, $value);
            }
        }
        return $array = $result;
    }
    /**
     * return all variants with plan based on filter and status.
     **/
    public static function fetchAllVariantsWithPlan($plans_id, $plan_options)
    {

        $handsetsId = $plan_options['variants_id'];
        $storage = isset($plan_options['storage']) ? $plan_options['storage'] : [];
        $contract_filter = isset($plan_options['contract_filter']) ? $plan_options['contract_filter'] : [];
        $ownLeaseFilter = isset($plan_options['own_lease']) ? $plan_options['own_lease'] : [];


        // $activeHandsetsHandsetId = PlanHandset::ApiStatus()->whereIn('plan_id', $plans_id)->whereIn('handset_id', $activeHandsetsId)->pluck('handset_id')->toArray();
        $plansMobileVariants = PlanVariant::ApiStatus()->whereIn('plan_id', $plans_id)->whereIn('handset_id', $handsetsId);

        $planVariantIds = [];
        $variant_ids = $plansMobileVariants->pluck('variant_id');

        if (!empty($storage)) {
            // $storage_variant_ids = Variant::whereIn('internal_stroage_id',$storage)->pluck('id')->toArray(); 
            $storage_variant_ids = Variant::whereIn('internal_stroage_id', $storage)->whereIn('id', $variant_ids)->pluck('id')->toArray();
            $planVariantIds = array_merge($storage_variant_ids, $planVariantIds);
            if (!empty($planVariantIds)) {
                $plansMobileVariants->whereIn('variant_id', array_unique($planVariantIds));
            }
        }
        if (!empty($contract_filter)) {
            $planContractTableIds = PlanContract::whereIn('plan_id', $plans_id)->where('status', 1)->whereIn('contract_id', $contract_filter)->pluck('plan_variant_id')->toArray();
            if (!empty($planContractTableIds)) {

                $plansMobileVariants->whereIn('variant_id', array_unique($planContractTableIds));
            }
        }


        $plansMobileVariants->with(['contracts' => function ($q) use ($contract_filter) {
            if (!empty($contract_filter)) {
                $q->whereIn('contract_id', $contract_filter);
            }
        }, 'handset', 'plan', 'plan.terms', 'plan.contract', 'plan.providers', 'plan.providers.logo', 'variant', 'variant.capacity', 'variant.internal', 'contracts.contract_data', 'plan.cost_type']);

        // print_r($plansMobileVariants->get()->toArray());die;


        if (!empty($ownLeaseFilter)) {
            if (in_array(1, $plan_options['own_lease']) && !in_array(2, $plan_options['own_lease'])) {
                $plansMobileVariants = $plansMobileVariants->where('own', 1);
            }
            if (in_array(2, $plan_options['own_lease']) && !in_array(1, $plan_options['own_lease'])) {
                $plansMobileVariants = $plansMobileVariants->where('lease', 1);
            }
        }

        $data = $plansMobileVariants->get();


        $response = [];
        if ($data) {
            //IF check own or lease type    
            if (!empty($plan_options['own_lease'])) {

                //make plan listing array 
                if (in_array(1, $plan_options['own_lease'])) {
                    $tempOwnPlanArr = self::makeOwnDataForPlanListing($data, $plan_options);
                }
                if (in_array(2, $plan_options['own_lease'])) {

                    $tempLeasePlanArr = self::makeLeaseDataForPlanListing($data, $plan_options);
                }
                //merge plan listing array according conditions 
                if (in_array(1, $plan_options['own_lease']) && in_array(2, $plan_options['own_lease'])) {
                    $response = array_merge($tempLeasePlanArr, $tempOwnPlanArr);
                } else {
                    if (in_array(1, $plan_options['own_lease'])) {
                        $response = $tempOwnPlanArr;
                    }
                    if (in_array(2, $plan_options['own_lease'])) {
                        $response = $tempLeasePlanArr;
                    }
                }
                //default plan listing   
            } else {

                $tempLeasePlanArr = self::makeLeaseDataForPlanListing($data, $plan_options);

                $tempOwnPlanArr = self::makeOwnDataForPlanListing($data, $plan_options);

                $response = array_merge($tempLeasePlanArr, $tempOwnPlanArr);
            }
            return $response;
        }
    }
    public static function makeOwnDataForPlanListing($data, $plan_options)
    {
        $commonDataForPlanListing = [];
        foreach ($data as  $value) {

            if ($value['own'] == 1) {
                $own_lease_type = 'own';
                $temp_contract = self::variantContractValidity($value, $own_lease_type);

                if (!empty($temp_contract['contract_id'])) {
                    $commonDataForPlanListing = self::commonDataForPlanListing($plan_options, $value, $own_lease_type, $temp_contract);
                    return $commonDataForPlanListing;
                }
            }
        }
    }



    public static function commonDataForPlanListing($plan_options, $value, $own_lease_type, $temp_contract)
    {

        $temp_arr = [];
        $temp_combined_array = [];
        $temp_arr['plan_tag'] = $own_lease_type;
        $temp_arr['plan_id'] = $value->plan_id;
        $temp_arr['handset_id'] = $value->handset_id;
        $temp_arr['variant_id'] = $value->variant_id;
        $temp_arr['own'] = $value->own;
        $temp_arr['lease'] = $value->lease;
        $temp_arr['own_cost'] = $value->own_cost;
        $temp_arr['plan_cost'] = $value->plan->cost;
        $temp_arr['lease_cost'] = $value->lease_cost;
        $temp_arr['combine_ids_for_unique_array'] = $value->plan_id . '' . $value->handset->name . '' . $value->variant->internal->storage_name . '' . $value->variant->capacity->capacity_name;
        if (!empty($plan_options['own_lease'])) {
            if (in_array(1, $plan_options['own_lease']) && in_array(2, $plan_options['own_lease'])) {
                $temp_arr['own_lease_selected_option'] = 'both';
            } else {
                $temp_arr['own_lease_selected_option'] = $own_lease_type;
            }
        } else {
            $temp_arr['own_lease_selected_option'] = 'both';
        }
        $temp_arr['storage'] =  $value->variant->internal->storage_name;

        $temp_arr = self::setPlanListingData($temp_arr, 'plan', $value);
        $temp_arr = self::setPlanListingData($temp_arr, 'handset', $value);
        $temp_arr = self::setPlanListingData($temp_arr, 'variant', $value);

        $temp_arr['contract_id'] =  $temp_contract['contract_id'];
        $temp_arr['variant_contract_validity'] = $temp_contract['max_lease_validity'];
        $temp_arr['variant_contract_cost'] = $temp_contract['cost'];
        $plan_cost = ($temp_arr['plan_cost']) / ($temp_arr['plan']['contract_validity']);
        $handset_cost = ($temp_arr['variant_contract_cost']) / ($temp_arr['variant_contract_validity']);
        $total_cost = ($plan_cost + $handset_cost);
        $temp_arr['total_cost'] = $total_cost;
        $temp_combined_array[] = $temp_arr;
        return $temp_combined_array;
    }
    public static function makeLeaseDataForPlanListing($data, $plan_options)
    {

        $commonDataForPlanListing = [];
        foreach ($data as  $value) {

            if (in_array(2, $plan_options['own_lease']) && !in_array(1, $plan_options['own_lease']) || ($value['lease'] == 1 && $value['own'] == 0)) {
                $own_lease_type = 'lease';
                $temp_contract = self::variantContractValidity($value, $own_lease_type);
                if (!empty($temp_contract['contract_id'])) {
                    $commonDataForPlanListing = self::commonDataForPlanListing($plan_options, $value, $own_lease_type, $temp_contract);
                }
                return $commonDataForPlanListing;
            }
        }
    }
    static function simPlanSorting($plan_options, $plans)
    {
        // if sort method is on cost based.
        foreach ($plans as $value) {
            $value->final_cost = number_format($value->cost / $value->contract->validity, 2);
        }
        if (isset($plan_options['sortMethod']) && $plan_options['sortMethod'] == 'cost') {
            if (strtolower($plan_options['sortBy']) == 'asc') {
                array_multisort(array_column($plans, 'final_cost'), SORT_ASC, $plans);
            } else {
                array_multisort(array_column($plans, 'final_cost'), SORT_DESC, $plans);
            }
        }
        return $plans;
    }
    /*
    * get sim plan based on cost filter applied
    */
    public static function fetchSimPlanBasedOnCost($plan, $plan_options)
    {
        $min_price = $plan_options['plan_cost_min'];
        $max_price = $plan_options['plan_cost_max'];
        $max_range = $plan_options['max_range'];
        $temp_arr = [];


        foreach ($plan as $value) {
            $temp_arr[] = $value;
            // $temp_cost = ($value->cost/$value->contract->validity);

            // if(($temp_cost>=$min_price) ){
            //     if($max_price==$max_range){
            //         $temp_arr[]= $value;
            //     }elseif($temp_cost<=$max_price){

            //         $temp_arr[]= $value;
            //     }
            // }
        }



        return $temp_arr;
    }
    /*
    * fetch plans based on normal filter applied like provider, data, cost, sort , current provider etc.
    */
    public static function fetchPlansBasedOnNormalFilter($plan, $plan_options)
    {

        $plan = self::fetchPlanBasedOnTime($plan);

        if (isset($plan_options['providers_filter']) && $plan_options['providers_filter']) {
            $plan = $plan->whereIn('provider_id', $plan_options['providers_filter']);
        }


        // Data Usage minimum filter 
        if (isset($plan_options['data_usage_min'])) {
            $plan = self::fetchPlanBasedOnData($plan, $plan_options);
        }

        return $plan;
    }
    public static function fetchPlanBasedOnData($plan, $plan_options)
    {
        $min_data = $plan_options['data_usage_min'];
        // if minimum data is unlimited
        if ($min_data == 'unlimited') {
            $plan = $plan->where('plan_data', '>=', 0);
        } else {
            //for all plan MB+GB
            if ($min_data == 0) {
                $plan = $plan->where('plan_data', '>=', $min_data);
            } else {
                //only GB 
                $plan = $plan->where('plan_data', '>=', $min_data)->where('plan_data_unit', 2);
            }
        }
        return $plan;
    }

    public static function fetchPlanBasedOnTime($plan)
    {

        $plan = $plan->where('activation_date_time', '<=', date('Y-m-d H:i:s'))
            ->where(function ($q) {
                $q->where(function ($qq) {
                    $qq->where('deactivation_date_time', '=', '')
                        ->orWhereNull('deactivation_date_time');
                })->orWhere('deactivation_date_time', '>=', date('Y-m-d H:i:s'));
            });

        return $plan;
    }
    static function simPlanContractFilter($plan_options, $plans)
    {

        $contracts = isset($plan_options['contract_filter']) ? $plan_options['contract_filter'] : [];
        if ($contracts) {
            $plans = $plans->whereIn('contract_id', $contracts);
        }
        return $plans;
    }

    // fetch variant contract validity.
    static function variantContractValidity($record, $own_lease_type)
    {

        $max_lease_validity = 0;
        $max_own_validity = 0;
        $contract_id = '';
        $cost = 0;
        if ($own_lease_type == 'lease') {
            $cost = $record->lease_cost;
            $temp_cost = 0;
            foreach ($record->contracts as $key => $value) {
                // echo "<pre>";print_r($value);die;
                if ($value->contract_type == 1) {
                    if (!empty($value->contract_data->validity)) {
                        if ($value->contract_data->validity > $max_lease_validity) {
                            $max_lease_validity = $value->contract_data->validity;
                            $contract_id = $value->contract_data->id;
                            if ($value->contract_cost && $value->contract_cost != null) {
                                $temp_cost = $value->contract_cost;
                            } else {
                                $temp_cost = $cost;
                            }
                        }
                    }
                }
            }
            $cost = $temp_cost;
        }
        if ($own_lease_type == 'own') {
            //  for own only
            $cost = $record->own_cost;

            $temp_cost = 0;
            foreach ($record->contracts as $key => $value) {

                if ($value->contract_type == 0) {
                    if (!empty($value->contract_data->validity)) {

                        if ($value->contract_data->validity > $max_own_validity) {

                            $max_own_validity = $value->contract_data->validity;
                            $contract_id = $value->contract_data->id;
                            if ($value->contract_cost && $value->contract_cost != null) {
                                $temp_cost = $value->contract_cost;
                            } else {
                                $temp_cost = $cost;
                            }
                        }
                    }
                }
            }
            $cost = $temp_cost;
        }

        return ['max_own_validity' => $max_own_validity, 'max_lease_validity' => $max_lease_validity, 'cost' => $cost, 'contract_id' => $contract_id];
    }

    /**
     * return all variants with plan.
     **/
    static function setPlanListingData($temp_arr, $case, $value)
    {

        switch ($case) {
            case 'plan':
                $temp_arr['plan']['id'] = $value->plan->id;
                $temp_arr['plan']['id'] = $value->plan->id;
                $temp_arr['plan']['provider_id'] = $value->plan->provider_id;
                $temp_arr['plan']['cost'] = $value->plan->cost;
                $temp_arr['plan']['name'] = $value->plan->name;
                //$temp_arr['plan']['details'] = $value->plan->details;
                $temp_arr['plan']['inclusion'] = $value->plan->inclusion;
                $temp_arr['plan']['plan_data_unit'] = $value->plan->plan_data_unit;
                $temp_arr['plan']['plan_data'] = $value->plan->plan_data;
                $temp_arr['plan']['contract_validity'] = $value->plan->contract->validity;
                $temp_arr['plan_type'] = $value->plan->plan_type;
                $temp_arr['provider_logo'] = \App\Models\ProviderLogo::FrontEndLogo($value->plan->provider_id);
                break;

            case 'handset':
                $temp_arr['handset']['image'] = $value->handset->image;
                $temp_arr['handset']['name'] = $value->handset->name;
                $temp_arr['handset']['id'] = $value->handset->id;
                break;

            case 'variant':
                $temp_arr['color']['title'] = $value->variant->color->title;
                $temp_arr['color']['id'] = $value->variant->color->id;
                $temp_arr['color_id'] = $value->variant->color_id;
                $temp_arr['capacity']['capacity_name'] = $value->variant->capacity->capacity_name;
                $temp_arr['capacity_id'] = $value->variant->capacity_id;
                // $temp_arr['internal']['storage_name'] = $value->variant->internal->storage_name;
                $temp_arr['internal_id'] = $value->variant->internal_stroage_id;
                $temp_arr['variant']['id'] = $value->variant->id;
                break;

            default:
                # code...
                break;
        }
        return $temp_arr;
    }


    static function setPlanListingResponse($plans, $filters,$hostNames)
    {
        $hostName = '';
        $hansetPlan = [];
        foreach ($plans->toArray() as $plan) {

           
            //foreach($plan['plan_variant'] as $handset){
                foreach($hostNames as $host){
                    if($plan['host_type'] == $host['local_id']){

                        $hostName = $host['name'];
                    }

                }



            if (count($plan['plan_variant'])) {

                foreach ($plan['plan_variant'] as $varient) {

                    if (count($varient['plan_contracts'])) {
                        $planData['plan_id'] = $plan['id'];
                        $planData['plan_name'] = $plan['name'];
                        $planData['own'] = $varient['own'];
                        $planData['lease'] = $varient['lease'];
                        $planData['own_cost'] = $varient['own_cost'];
                        $planData['lease_cost'] = $varient['lease_cost'];
                        $planData['own_lease_selected_option'] = "both";
                        $planData['image'] = $varient['variant']['images'];
                        $planData['color'] = $varient['variant']['color'];
                        $planData['capacity'] = $varient['variant']['capacity'];
                        $planData['internal'] = $varient['variant']['internal'];
                        $planData['variant'] = $varient['variant'];

                        $planData['handset_id'] = $varient['handset_id'];
                        $planData['inclusion'] = $plan['inclusion'];
                        $planData['plan_type'] = $plan['plan_type'];
                        $planData['connection_type'] = $plan['connection_type'];

                        $planData['inclusion'] = $plan['inclusion'];
                        $planData['cost'] = $plan['cost'];
                        $planData['plan_data'] = $plan['plan_data'];
                        $planData['plan_data_unit'] = $plan['plan_data_unit'];
                        $planData['cost_type_id'] = $plan['cost_type_id'];
                        $planData['sim_type'] = $plan['sim_type'];
                        $planData['network_type'] = $plan['network_type'];
                        $planData['host_type'] = $hostName;
                        $planData['cost_type'] = $plan['cost_type'];
                        $planData['contract'] = $plan['contract'];
                        $planData['provider'] = $plan['providers'];
                        $planData['plan_list_logo']['name'] = $plan['providers']['plan_list_logo']['name'];
                        $planData['handset'] = $varient['handset'];


                        $handsetCalculation =  self::calculatePlanCost($varient, $filters, $plan['cost'], $plan['contract']['validity']);

                        if (($handsetCalculation)) {
                            $planData['total_cost'] = round($handsetCalculation['total_cost'], 2);
                            $planData['complete_cost'] = round($handsetCalculation['complete_cost'], 2);
                            $planData['sim_contract'] = $handsetCalculation['sim_contract'];
                            $planData['handset_contract'] = $handsetCalculation['phone_contract'];
                            $planData['sim_cost'] = round($handsetCalculation['sim_cost'], 2);
                            $planData['handset_cost'] = round($handsetCalculation['handset_cost'], 2);
                            $hansetPlan[] = $planData;
                        }
                    }
                }
            }




            // }
        }
        return $hansetPlan;
    }

    static function calculatePlanCost($plans, $filters, $planCost, $simValidity)
    {


        $handsetFinalCost = [];
        $handsetCost['phone_contract'] = '';
        $handsetCost['cost'] = '';
        $ownContract = [];
        $leaseContract = [];
        if (isset($filters['contract_filter']) && count($filters['contract_filter'])) {

            usort($plans['plan_contracts'], function ($a, $b) {
                return $a['contract']['validity'] > $b['contract']['validity'];
            });

            foreach ($plans['plan_contracts'] as $cont) {
                if ($cont['contract_type'] == 0) {
                    $ownContract[] = $cont;
                } elseif ($cont['contract_type'] == 1) {
                    $leaseContract[] = $cont;
                }
            }
            
            if ((in_array(1, $filters['own_lease']))) {
                $contract = isset($ownContract[0]) ? $ownContract[0] : [];
                
                if (isset($contract['contract_type']) && $contract['contract_type'] == 0) {
                    if ($contract['contract_cost'] == null) {
                        $handsetCost['cost'] = $plans['own_cost'];
                        $handsetCost['phone_contract'] = $contract['contract']['validity'];
                    } else {
                        $handsetCost['cost'] = $contract['contract_cost'];
                        $handsetCost['phone_contract'] = $contract['contract']['validity'];
                    }
                } else {
                    return 0;
                }
            } elseif (in_array(2, $filters['own_lease'])) {

                $contract = isset($leaseContract[0]) ? $leaseContract[0] : [];

                if (isset($contract['contract_type']) && $contract['contract_type'] == 1) {

                    if ($contract['contract_cost'] == null) {

                        $handsetCost['cost'] = $plans['own_cost'];
                        $handsetCost['phone_contract'] = $contract['contract']['validity'];
                    } else {
                        $handsetCost['cost'] = $contract['contract_cost'];
                        $handsetCost['phone_contract'] = $contract['contract']['validity'];
                    }
                } else {
                    return 0;
                }
            }
        } else {
           
            usort($plans['plan_contracts'], function ($a, $b) {
                return $a['contract']['validity'] > $b['contract']['validity'];
            });

            foreach ($plans['plan_contracts'] as $cont) {
                if ($cont['contract_type'] == 0) {
                    $ownContract[] = $cont;
                } elseif ($cont['contract_type'] == 1) {
                    $leaseContract[] = $cont;
                }
            }
            $handsetCost['phone_contract'] = '';
            $handsetCost['cost'] = '';
            
            if ((in_array(1, $filters['own_lease']))) {
              
                $contract = isset($ownContract[0]) ? $ownContract[0] : [];
               
                if (isset($contract['contract_type']) && $contract['contract_type'] == 0) {
                    if ($contract['contract_cost'] == null) {
                      
                        $handsetCost['cost'] = $plans['own_cost'];
                        $handsetCost['phone_contract'] = $contract['contract']['validity'];
                        $handsetCost['contract_name'] = $contract['contract']['contract_name'];
                    } else {
                        $handsetCost['cost'] = $contract['contract_cost'];
                        $handsetCost['phone_contract'] = $contract['contract']['validity'];
                        $handsetCost['contract_name'] = $contract['contract']['contract_name'];
                    }
                } else {
                    $handsetCost['cost'] = $plans['own_cost'];
                    $handsetCost['phone_contract'] = 1;
                }
            } elseif (in_array(2, $filters['own_lease'])) {
                
                $contract = $leaseContract[0];
                $contract = isset($leaseContract[0]) ? $leaseContract[0] : [];
                if (isset($contract['contract_type']) && $contract['contract_type'] == 1) {

                    if ($contract['contract_cost'] == null) {

                        $handsetCost['cost'] = $plans['lease_cost'];
                        $handsetCost['phone_contract'] = $contract['contract']['validity'];
                        $handsetCost['contract_name'] = $contract['contract']['contract_name'];
                    } else {

                        $handsetCost['cost'] = $contract['contract_cost'];
                        $handsetCost['phone_contract'] =  $contract['contract']['validity'];
                        $handsetCost['contract_name'] = $contract['contract']['contract_name'];
                    }
                } else {
                    $handsetCost['cost'] = $plans['lease_cost'];
                    $handsetCost['phone_contract'] = 1;
                }
            }
        }
        if (isset($handsetCost['phone_contract'])) {
            $hansetTotalCost =  $handsetCost['cost'] / $handsetCost['phone_contract'];
            $simCost =  $planCost ;
            $simTotalCost =  $planCost;
            $handsetFinalCost['total_cost'] = $hansetTotalCost + $simTotalCost;
            $handsetFinalCost['complete_cost'] = $handsetCost['cost'] + $simCost;
            $handsetFinalCost['sim_contract'] = $simValidity;
            $handsetFinalCost['sim_cost'] = $simCost;
            $handsetFinalCost['handset_cost'] = $hansetTotalCost;
            $handsetFinalCost['phone_contract'] = $handsetCost['phone_contract'];
        }


        return $handsetFinalCost;
    }

    static function calculateSimCost($plans){

        foreach($plans as $plan){
            $simMontlyCost='';
            $contract =$plan['contract']['validity'];
            $simMontlyCost = round ($plan['cost']/$contract,2);
            $plan['monlty_cost']=$simMontlyCost;
            $finalPlans[]=$plan;
        }
        return $finalPlans;
    }
}
