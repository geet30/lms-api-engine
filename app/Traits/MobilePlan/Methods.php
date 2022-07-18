<?php

namespace App\Traits\MobilePlan;

use Illuminate\Support\Facades\DB;
use App\Models\{PlanTelcoContents, PlanRefs, Contract, PlanMobile, ConnectionType, Handset, Brand, HandsetInfo, PlanVariant, Variant_images, Variant, LeadJourneyDataMobile};

trait Methods
{

    /**
     * Get Services or Single Service.
     * @param string  planId
     * @return object keyData
     */

    static function getMobileTerms($planId = null)
    {

        if ($planId) {

            $query = PlanTelcoContents::where('plan_id', $planId)->whereNotNull('slug')->where('description', '!=', '')->get(['title', 'description']);

            if (count($query) > 0) {
                return $query;
            }
            return false;
        }
    }
    static function getMobileCriticalInfo($planId = null)
    {

        $query = PlanRefs::where('plan_id', $planId)->where('status', 1)->orderBy('s_no')->get(['s_no', 'title', 'url']);

        if (count($query) > 0) {
            return $query;
        }
        return false;
    }
    static function getPlanDetails($request)
    {
        
        
        $data = [];
        $variant = PlanVariant::where([
            'plan_id'    => $request->plan_id,
            'handset_id' => $request->handset_Id
        ])->pluck('variant_id')->toArray();
        if ($request->plan_type == 1) {
            $data =   PlanMobile::where([
                'id'    => $request->plan_id
            ])->where('plan_type',1)->with('contract','PlanHostType','fees.costType','fees.feeType')->first();
            return  $data;
        } else if ($request->plan_type == 2) {
            $data = PlanVariant::with(['plan.contract','plan.fees.costType','plan.fees.feeType', 'plan.providers.PlanListLogo','plan.PlanHostType', 'handset', 'handset.opratingSystem','handset.brand','variant', 'variant.color', 'variant.internal', 'variant.internal', 'variant.all_images','variant.capacity', 'PlanContracts.contract'])->Where('plan_id', $request->plan_id)->Where('handset_id', $request->handset_Id)->Where('variant_id', $request->variant_id)->first();
           
            $PlanData=   self::calculatePlanDetailCost($data);
            return $PlanData;
        } 
    }
    static function getPlanDetailsOld($request)
    {
        $plan_id = (isset($request->plan_id)) ? $request->plan_id : 0;

        // $plan_data = PlanMobile::where('id', $plan_id)->where('status', 1)->with('PlanContract')->first();

        // if ($request->has('handset_Id')) {

        //     $handsetId = $request->handset_Id;
        //     $handset_data = Handset::where('id', $handsetId)->first();

        //     $brand_id = $handset_data->brand_id ?? 0;

        //     $brand_name = Brand::where('id', $brand_id)->value('title'); // handset brand Info.

        //     $brand_name = $brand_name ?? '';
        //     $handset_other_info = HandsetInfo::where('handset_id', $handsetId)->get(['s_no', 'title', 'image', 'linktype']);
        //     $planResult = collect($plan_data);
        //     $planResult->put('handset_other_info', $handset_other_info);
        //     $planResult->put('handset_data', $handset_data);



        //     if ($request->own_or_lease == 1) {
        //         // own case
        //         $temp_res = self::getOwnPlanVariantsColor($handsetId, $plan_id);
        //         $planResult->put('planVariantResult', $temp_res);
        //     } elseif ($request->own_or_lease == 2) {
        //         // lease case
        //         $temp_res = self::getLeasePlanVariantsColor($handsetId, $plan_id);
        //         $planResult->put('planVariantResult', $temp_res);
        //     }

        //     $plan_data =  $planResult;
        // }






        if (!$plan_data) {
            return false;
        }


        return $plan_data;


        // switch ($plan_id) {
        //     case ConnectionType::PLAN_TYPE_SIM:
        //         $plan_data = PlanMobile::where('id', $plan_id)->where('status', 1)->first();

        //         if (!$plan_data) {
        //             return false;
        //         }
        //         else
        //         {
        //             // $plan_data->increment('visit_counts');
        //             $planName = $plan_data->name;
        //             $contract_validity = Contract::where('id', $plan_data->contract_id)->value('validity');
        //             $plan_data = collect($plan_data);
        //             $plan_data->put('plan_name', $planName);
        //             $plan_data->put('contract_validity', $contract_validity);
        //             return $plan_data;
        //         }
        //         break;
        //     case ConnectionType::PLAN_TYPE_MOBILE:

        //         $buythrough = (isset($request->own_or_lease)) ? $request->own_or_lease :0;
        //         // $colorId = (isset($request->colorId)) ? $request->colorId :0;
        //         $handsetId = (isset($request->handset_Id)) ? $request->handset_Id :0;

        //         $plan_data = PlanMobile::where('id', $plan_id)->first();
        //         if (!$plan_data) {
        //            return false;
        //         }
        //         else
        //         {
        //             // $plan_data->increment('visit_counts');

        //             $handset_data = Handset::where('id', $handsetId)->first();

        //             $brand_id = $handset_data->brand_id ?? 0;

        //             $brand_name = Brand::where('id', $brand_id)->value('title'); // handset brand Info.

        //             $brand_name = $brand_name ?? '';
        //             $handset_other_info = HandsetInfo::where('handset_id', $handsetId)->get(['s_no', 'title', 'image', 'linktype']);

        //             $is_pre_order = $plan_data->is_pre_order == 0 ? 'No' : 'Yes';
        //             $is_card_slot = $plan_data->is_card_slot == 0 ? 'No' : 'Yes';
        //             $planContractValidity = $plan_data->contract->validity;
        //             $planName = $plan_data->name;
        //             $planResult = collect($plan_data);
        //             $planResult->put('plan_name', $planName);
        //             $planResult->put('handset_name', Handset::where('id', $handsetId)->value('name'));
        //             $planResult->put('contract_validity', $planContractValidity);
        //             $planResult->put('brand_name', $brand_name);
        //             $planResult->put('is_pre_order', $is_pre_order);
        //             $planResult->put('is_card_slot', $is_card_slot);
        //             $planResult->put('handset_other_info', $handset_other_info);
        //             $planResult->put('handset_data', $handset_data);

        //             if($buythrough=='own'){
        //                 // own case
        //                 $temp_res = self::getOwnPlanVariantsColor($handsetId, $plan_id);
        //                 $planResult->put('planVariantResult', $temp_res);
        //             }else{

        //                 // lease case
        //                 $temp_res = self::getLeasePlanVariantsColor($handsetId, $plan_id);
        //                 $planResult->put('planVariantResult', $temp_res);
        //             }
        //             return $planResult;
        //         }
        //         break;
        //         default:

        //          return false;
        //         break;
        // }


    }
    static function getLeasePlanVariantsColor($handsetId, $plan_id)
    {
        try {
            $result = [];
            $color_array = [];
            $all_variants = PlanVariant::where('lease', 1)->where('handset_id', $handsetId)->where('plan_id', $plan_id)->ApiStatus()->whereNull('deleted_at')->with(['variant', 'variant.api_color'])->get();
            $temp_arr = [];
            foreach ($all_variants as $value) {
                $color_id = $value->variant->color_id;
                $ramArray = self::fetchColorBasedLeasePlanRam($handsetId, $plan_id, $color_id);
                if (!in_array($color_id, $temp_arr)) {
                    $temp_arr[] = $color_id;
                    $color_array[] = [
                        'variant_id' => $value->variant_id,
                        'color_id' => $color_id,
                        'images' => Variant_images::where('handset_id', $handsetId)->where('variant_id', $value->variant_id)->orderBy('sr_no')->whereNull('deleted_at')->pluck('image'),
                        'title' => $value->variant->api_color->title ?? '',
                        'hexacode' => $value->variant->api_color->hexacode ?? '',
                        'ram' => $ramArray,
                    ];
                }
            }

            $result = [
                'color' => $color_array ?? [],

            ];
            return $result;
        } catch (\Exception $e) {
            $result = [
                'color' => [],
                'ram' => [],
                'storage' => [],
                'contract' => [],
            ];
            return $result;
        }
    }
    static function fetchColorBasedLeasePlanRam($handsetId, $plan_id, $color_id)
    {
        try {
            $result = [];
            $variant_ids = Variant::where('handset_id', $handsetId)->where('color_id', $color_id)->where('status', 1)->pluck('id');
            $all_variants = PlanVariant::where('lease', 1)->where('handset_id', $handsetId)->whereIn('variant_id', $variant_ids)->where('plan_id', $plan_id)->ApiStatus()->whereNull('deleted_at')->with(['variant', 'variant.api_capacity', 'variant.api_internal', 'contracts'])->get();
            $temp_array = [];
            foreach ($all_variants as $value) {
                $capacity_id = $value->variant->capacity_id;
                if (!in_array($capacity_id, $temp_array)) {
                    $temp_array[] = $capacity_id;
                    $storage = self::fetchRamBasedLeasePlanStorage($handsetId, $plan_id, $color_id, $capacity_id);
                    $result[] = [
                        'variant_id' => $value->variant_id,
                        'images' => Variant_images::where('handset_id', $handsetId)->where('variant_id', $value->variant_id)->orderBy('sr_no')->whereNull('deleted_at')->pluck('image'),
                        'ram_id' => $capacity_id,
                        'ram_value' => $value->variant->api_capacity->value ?? '',
                        'ram_name' => $value->variant->api_capacity->capacity_name ?? '',
                        'storage' => $storage,
                        'color_id' => $color_id,
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
    // return plan variants colors ram storage.
    static function getOwnPlanVariantsColor($handsetId, $plan_id)
    {
        try {
            $result = [];
            $color_array = [];
            $all_variants = PlanVariant::where('own', 1)->where('handset_id', $handsetId)->where('plan_id', $plan_id)->ApiStatus()->whereNull('deleted_at')->with(['variant', 'variant.api_color'])->get();
            $temp_arr = [];
            foreach ($all_variants as  $value) {
                $color_id = $value->variant->color_id;
                $ramArray = self::fetchColorBasedOwnPlanRam($handsetId, $plan_id, $color_id);
                if (!in_array($color_id, $temp_arr)) {
                    $temp_arr[] = $color_id;
                    $color_array[] = [
                        'variant_id' => $value->variant_id,
                        'color_id' => $color_id,
                        'title' => $value->variant->api_color->title ?? '',
                        'images' => Variant_images::where('handset_id', $handsetId)->where('variant_id', $value->variant_id)->orderBy('sr_no')->whereNull('deleted_at')->pluck('image'),
                        'hexacode' => $value->variant->api_color->hexacode ?? '',
                        'ram' => $ramArray,
                    ];
                }
            }

            $result = [
                'color' => $color_array ?? [],
            ];
            return $result;
        } catch (\Exception $e) {
            $result = [
                'color' => [],
                'ram' => [],
                'storage' => [],
                'contract' => [],
            ];
            return $result;
        }
    }
    // // fetch ram values
    static function fetchColorBasedOwnPlanRam($handsetId, $plan_id, $color_id)
    {
        try {
            $result = [];
            $variant_ids = Variant::where('handset_id', $handsetId)->where('color_id', $color_id)->where('status', 1)->pluck('id');
            $all_variants = PlanVariant::where('own', 1)->where('handset_id', $handsetId)->whereIn('variant_id', $variant_ids)->where('plan_id', $plan_id)->ApiStatus()->whereNull('deleted_at')->with(['variant', 'variant.api_capacity', 'variant.api_internal', 'contracts'])->get();

            $temp_array = [];
            foreach ($all_variants as $value) {
                $capacity_id = $value->variant->capacity_id;
                if (!in_array($capacity_id, $temp_array)) {
                    $temp_array[] = $capacity_id;
                    $storage = self::fetchRamBasedOwnPlanStorage($handsetId, $plan_id, $color_id, $capacity_id);
                    $result[] = [
                        'variant_id' => $value->variant_id,
                        'images' => Variant_images::where('handset_id', $handsetId)->where('variant_id', $value->variant_id)->orderBy('sr_no')->whereNull('deleted_at')->pluck('image'),
                        'ram_id' => $capacity_id,
                        'ram_value' => $value->variant->api_capacity->value ?? '',
                        'ram_unit' => $value->variant->api_capacity->unit ?? '',
                        'ram_name' => $value->variant->api_capacity->capacity_name ?? '',
                        'storage' => $storage,
                        'color_id' => $color_id
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
    // // fetch storage
    static function fetchRamBasedOwnPlanStorage($handsetId, $plan_id, $color_id, $ram_id)
    {
        try {
            $result = [];
            $variant_ids = Variant::where('handset_id', $handsetId)->where('color_id', $color_id)->where('capacity_id', $ram_id)->where('status', 1)->pluck('id');
            $all_variants = PlanVariant::where('own', 1)->where('handset_id', $handsetId)->whereIn('variant_id', $variant_ids)->where('plan_id', $plan_id)->ApiStatus()->whereNull('deleted_at')->with(['variant', 'variant.api_internal', 'contracts'])->get();
            $temp_arr = [];
            foreach ($all_variants as  $value) {
                $storage_id = $value->variant->internal_stroage_id;
                if (!in_array($storage_id, $temp_arr)) {
                    $temp_arr[] = $storage_id;
                    $contract = self::fetchStorageBasedOwnPlanContracts($handsetId, $plan_id, $color_id, $ram_id, $storage_id);
                    $result[] = [
                        'variant_id' => $value->variant_id,
                        'storage_id' => $storage_id,
                        'storage_value' => $value->variant->api_internal->value ?? '',
                        'storage_name' => $value->variant->api_internal->storage_name ?? '',
                        'contract' => $contract,
                        'ram_id' => $ram_id
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }
    // // fetch contracts
    static function fetchStorageBasedOwnPlanContracts($handsetId, $plan_id, $color_id, $ram_id, $storage_id)
    {
        try {
            $result = [];
            $variant_ids = Variant::where('handset_id', $handsetId)->where('color_id', $color_id)->where('capacity_id', $ram_id)->where('internal_stroage_id', $storage_id)->where('status', 1)->pluck('id');
            $all_variants = PlanVariant::where('own', 1)->where('handset_id', $handsetId)->whereIn('variant_id', $variant_ids)->where('plan_id', $plan_id)->ApiStatus()->whereNull('deleted_at')->with(['contracts', 'contracts.contract_data'])->get();
            foreach ($all_variants as  $value) {
                $plan = PlanMobile::where('id', $plan_id)->with('contract')->first();
                $plan_cost = $plan->cost / $plan->contract->validity;
                $cost = $value->own_cost;
                $temp_cost = 0;
                $contract = [];
                foreach ($value->contracts as $i => $val) {
                    if ($val->contract_type == 0) {
                        if ($val->contract_cost && $val->contract_cost != null) {
                            $temp_cost = $val->contract_cost;
                        } else {
                            $temp_cost = $cost;
                        }
                        $final_cost = $temp_cost / $val->contract_data->validity;
                        $contract[] = [
                            'variant_id' => $value->variant_id,
                            'contract_id' => $val->id,
                            'validity' => $val->contract_data->validity,
                            'plan_validity' => $plan->contract->validity,
                            'cost' => $temp_cost,
                            'final_cost' => number_format($final_cost, 2),
                            'plan_cost' => number_format($plan_cost, 2),
                            'min_cost' => number_format(($plan->cost + $temp_cost), 2),
                            'total_cost' => number_format(($plan_cost + $final_cost), 2),
                        ];
                    }
                }
                $result[] = [
                    'variant_id' => $value->variant_id,
                    'contract' => $contract,
                    'storage_id' => $storage_id
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    static public function planCompareDetails($request)
    {
        try {
            $fee = \DB::table('fees')->get();
            $plans = array_column($request->compare_data, 'plan_id');
            if ($request->plan_type == 1) {
                $data =   PlanMobile::whereIn('id', $plans)->with('contract','fees.costType','fees.feeType','providers')->get();
                $plans= [];
                foreach ($data as $plan){
                    $plans['compare_data'][] = ['plan'=>$plan];
                }
                $plans['master_fee'] = $fee;
                return $plans;
            } else if ($request->plan_type == 2) {
                $handsets = array_column($request->compare_data, 'handset_id');
                $variants = array_column($request->compare_data, 'variant_id');
                $compare = PlanVariant::with(['plan','plan.fees.costType','plan.fees.feeType','plan.providers.PlanListLogo','handset', 'variant', 'variant.color', 'variant.internal', 'variant.internal', 'variant.all_images'])->WhereIn('plan_id', $plans)->WhereIn('handset_id', $handsets)->WhereIn('variant_id', $variants)->get()->toArray();
                
                $data['master_fees'] = $fee;
                $data['compare_data'] = $compare;
            }
            return $data;
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PLANDETAIL_ERROR_CODE, __FUNCTION__);
        }
    }
    static function  calculatePlanDetailCost($plans)
    {
        $plans=$plans->toArray();
        $finalContracts = [];
       // dd($plans->toArray());
        $planCost = $plans['plan']['cost'];
        $simValidity = $plans['plan']['contract']['validity'];
        foreach ($plans['plan_contracts'] as $contract) {

            if ($contract['contract_type'] == 0) {
                if ($contract['contract_cost'] == null) {
                    $handsetCost['cost'] = $plans['own_cost'];
                    $handsetCost['phone_contract'] = $contract['contract']['validity'];
                } else {
                    $handsetCost['cost'] = $contract['contract_cost'];
                    $handsetCost['phone_contract'] = $contract['contract']['validity'];
                }
            } elseif ($contract['contract_type'] == 1) {
                if ($contract['contract_cost'] == null) {
                    $handsetCost['cost'] = $plans['lease_cost'];
                    $handsetCost['phone_contract'] = $contract['contract']['validity'];
                } else {
                    $handsetCost['cost'] = $contract['contract_cost'];
                    $handsetCost['phone_contract'] = $contract['contract']['validity'];
                }
            }


           
            if (isset($handsetCost['phone_contract'])) {
                $hansetTotalCost =  $handsetCost['cost'] / $handsetCost['phone_contract'];
                $simCost =  $planCost;
                $simTotalCost =  $planCost;
                $handsetFinalCost['total_cost'] = $handsetCost['cost'] + $simCost;
                $handsetFinalCost['complete_cost'] = $hansetTotalCost + $simTotalCost;
                $handsetFinalCost['sim_contract'] = $simValidity;
                $handsetFinalCost['sim_cost'] = $simCost;
                $handsetFinalCost['handset_cost'] = $hansetTotalCost;
                $handsetFinalCost['phone_contract'] = $handsetCost['phone_contract'];

                $contract['total_cost'] = round($handsetCost['cost'] + $simCost,2);
                $contract['complete_cost'] =round( $hansetTotalCost + $simTotalCost,2);
                $contract['sim_contract'] = $simValidity;
                $contract['sim_cost'] = round($simCost,2);
                $contract['handset_cost'] = round($hansetTotalCost,2);
                $contract['phone_contract'] = $handsetCost['phone_contract'];
            }
            $finalContracts[]=$contract;
        }
        unset( $plans['plan_contracts']);
        $plans['contracts_data']= $finalContracts;
        
        return $plans;
    }
}
