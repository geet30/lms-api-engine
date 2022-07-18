<?php

namespace App\Traits\Visitor;

use Illuminate\Support\Facades\DB;
use App\Models\{Lead, SaleProductsEnergy, SaleProductsBroadbandAddon};

/**
 * Visitor Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    /**
     * Validate Postcode.
     * Author: Sandeep Bangarh
     * @param string $postCode
     */
    static function validatePostcode($postCode)
    {
        $postCode = stripslashes($postCode);
        $postCodeArr = explode(',', $postCode);
        if (count($postCodeArr) == 3) {
            return true;
        }
        return false;
    }

    static function getOTPData($visitor, $request, $leadId)
    {
        $service = Lead::getService();
        $table = 'plans_mobile';
        $columns = ['name', 'cost', 'details', 'contract_id'];
        if ($service == 'broadband') {
            $table = 'plans_broadbands';
            $columns = ['name', 'plan_cost as cost', 'additional_plan_information as details', 'contract_id','download_speed','upload_speed','connection_type','data_limit','data_unit_id','satellite_inclusion'];
        }
        $plan = DB::table($table)->select($columns)->find($visitor->plan_id);
        if (!$plan) {
            return 0;
        }
        
        $planContract = null;
        $provider = DB::table('providers')->select('name')->where('user_id', $visitor->provider_id)->first();
        $prepareData = self::removeGDPR((array) $visitor);
        if ($service == 'mobile') {
            $mobileData = self::getMobileData($request, $visitor, $plan, $prepareData);
            $planContract = $mobileData['planContract'];
            $prepareData = $mobileData['prepareData'];
            $prepareData['plan_type'] = $visitor->plan_type;
        }

        if ($service == 'broadband') {
            $planContract = DB::table('contract')->select('contract_name', 'validity')->where('id', $plan->contract_id)->first();
            $prepareData = self::getBroadbandData($visitor, $plan, $prepareData);
        }

        $prepareData['plan_name'] = $plan->name;
        $prepareData['provider_name'] = $provider->name;
        
        $prepareData['plan_details'] = $plan->details;
        $data = self::getIdCardData($leadId);
        $prepareData = self::setAddresses($prepareData, $visitor);
        $prepareData['plan_duration'] = $planContract ? $planContract->contract_name : 'N/A';
        $prepareData['plan_validity'] = $planContract ? $planContract->validity : 'N/A';
        $prepareData['id_card_number'] = $data['idNumber'];
        $prepareData['expire_date'] = $data['expireDate'];
        foreach($prepareData as $key => $prepareDat) {
            $prepareData[$key] = $prepareDat??'N/A';
        }
        
        return $prepareData;
    }

    static function setAddresses ($prepareData, $visitor) {
        $prepareData['billing_address'] = $prepareData['delivery_address'] = null;
        switch ($visitor->billing_preference) {
            case 1:
                $prepareData['billing_address'] = $prepareData['email'];
                break;
            case 2:
                $prepareData['billing_address'] = $prepareData['address'];
                break;
            case 3:
                $billingDetail = DB::table('visitor_addresses')->select('address')->find($visitor->billing_address_id);
                $prepareData['billing_address'] = $billingDetail ? $billingDetail->address : 'N/A';
                break;
            
            default:
                break;
        }

        switch ($visitor->delivery_preference) {
            case 1:
                $prepareData['delivery_address'] = $prepareData['address'];
                break;
            case 2:
                $deliveryDetail = DB::table('visitor_addresses')->select('address')->find($visitor->delivery_address_id);
                $prepareData['delivery_address'] = $deliveryDetail ? $deliveryDetail->address : 'N/A';
                break;
            
            default:
                break;
        }

        return $prepareData;

    }

    static function getBroadbandData ($visitor, $plan, $prepareData) {
        $saleProductAddons = DB::table('sale_products_broadband_addon')->where('sale_product_id', $visitor->product_id)->get();
        $phoneLine = $saleProductAddons->where('category_id', 3)->first();
        $modem = $saleProductAddons->where('category_id', 4)->first();
        $addon = $saleProductAddons->where('category_id', 5)->first();
        $prepareData['phone_line'] = $prepareData['modem'] = $prepareData['addon'] = 'N\A';
        if ($phoneLine) {
            $phoneLineModel = DB::table('broadband_home_connection')->select('call_plan_name')->find($phoneLine->addon_id);
            $prepareData['modem'] = $phoneLineModel?$phoneLineModel->call_plan_name:'N\A';
        }
        if ($modem) {
            $modemModel = DB::table('broadband_modem')->select('modem_modal_name')->find($modem->addon_id);
            $prepareData['phone_line'] = $modemModel?$modemModel->modem_modal_name:'N\A';
        }
        if ($addon) {
            $otherModel = DB::table('broadband_other_addons')->select('addon_name')->find($addon->addon_id);
            $prepareData['addon'] = $otherModel?$otherModel->addon_name:'N\A';
        }
        $connectionData = DB::table('connection_types')->select('name')->where('local_id', $plan->connection_type)->where('service_id', 3)->where('status', 1)->first();
       
        $prepareData['download_speed'] = $plan->download_speed??'N/A';
        $prepareData['upload_speed'] = $plan->upload_speed??'N/A';
        $prepareData['satellite_inclusion'] = $plan->satellite_inclusion??'N/A';
        $prepareData['connection_name'] = $connectionData->name??'N/A';
        
        $dataUnit = 'N\A';
        switch ($plan->data_unit_id) {
            case 1:
                $dataUnit = 'KB';
                break;
            case 2:
                $dataUnit = 'MB';
                break;
            case 3:
                $dataUnit = 'GB';
                break;
            
            default:
                $dataUnit = 'TB';
                break;
        }
        $prepareData['data_limit'] = $plan->data_limit.' '.$dataUnit;
        return $prepareData;
    }

    static function getMobileData($request, $visitor, $plan, $prepareData)
    {
        $variant = DB::table('plans_mobile_variants')->select('own', 'lease', 'own_cost', 'lease_cost')->where('plan_id', $visitor->plan_id)->where('handset_id', $request->handset_id)->where('variant_id', $request->variant_id)->first();

        if (!$variant && $visitor->plan_type == 2) {
            return 1;
        }
        $handsetCost = [];
        $contractCost = DB::table('plan_contracts')->where('plan_id', $visitor->plan_id)->where('plan_variant_id', $request->variant_id)->select('contract_id', 'contract_type', 'contract_cost')->get();
        $contractValidity = null;

        $planContract = DB::table('contract')->select('contract_name', 'validity')->where('id', $plan->contract_id)->first();

        if (!$contractCost->isEmpty() && $visitor->plan_type == 2) {
            $ownContract = $contractCost->where('contract_type', 0)->first();
            $contractValidity = DB::table('contract')->where('id', $ownContract->contract_id)->value('validity');
            $handsetCost['phone_contract'] = $contractValidity;
            $handsetCost['cost'] = $ownContract->contract_cost;

            if ($ownContract) {
                $handsetCost['cost'] = $variant->own_cost;
            }

            $leaseContract = $contractCost->where('contract_type', 1)->first();
            if ($leaseContract) {
                $leaseContractValidity = DB::table('contract')->where('id', $leaseContract->contract_id)->value('validity');
                $handsetCost['cost'] = $variant->lease_cost;
                $handsetCost['phone_contract'] = $leaseContractValidity;
            }

            $hansetTotalCost =  $handsetCost['cost'] * $handsetCost['phone_contract'];
            $simCost =  $plan->cost / $contractValidity ?? 1;
            $simTotalCost =  $plan->cost;
        }

        $handsetVariant = [];
        if ($visitor->plan_type == 2) {
            $handsetVariant = DB::table('handset_variant')
                ->select('variant_name', 'title as color', 'internal_storages.value as storages_value', 'internal_storages.unit as storages_unit', 'capacities.value as ram_capacity_value', 'capacities.unit as ram_capacity_unit')
                ->leftjoin('colors', 'handset_variant.color_id', '=', 'colors.id')
                ->leftjoin('internal_storages', 'handset_variant.internal_stroage_id', '=', 'internal_storages.id')
                ->leftjoin('capacities', 'handset_variant.capacity_id', '=', 'capacities.id')
                ->where('handset_id', $request->handset_id)
                ->where('variant_id', $request->variant_id)
                ->first();

            $prepareData['mobile_plan_type'] = $variant->own??'N/A';

            if ($contractCost) {
                // $prepareData['total_cost'] = round($handsetCost['cost'] + $simCost, 2);
                // $prepareData['complete_cost'] = round(($hansetTotalCost + $simTotalCost), 2);
                $prepareData['sim_contract'] = $contractValidity??'N/A';
                // $prepareData['handset_contract'] = $handsetCost['phone_contract'];
                // $prepareData['sim_cost'] = round($simCost, 2);
                // $prepareData['handset_cost'] = round($hansetTotalCost, 2);
            }

            $prepareData = array_merge($prepareData, (array) $handsetVariant);
        }
        $connectionDetail = DB::table('sale_product_mobile_connection_details')->select('connection_request_type')->where('mobile_connection_id', $visitor->product_id)->first();
        $prepareData['connection_request_type'] = $connectionDetail ? $connectionDetail->connection_request_type : 'N/A';

        return ['planContract' => $planContract, 'prepareData' => $prepareData];
    }

    static function getIdCardData($leadId)
    {
        $identificationDetail = DB::table('visitor_identifications')->where('lead_id', $leadId)->first();
        $idNumber = $expireDate = 'N/A';
        if ($identificationDetail) {
            switch ($identificationDetail->identification_type) {
                case 'Foreign Passport':
                    $idNumber = $identificationDetail->foreign_passport_number;
                    $expireDate = $identificationDetail->foreign_passport_expiry_date;
                    break;
                case 'Passport':
                    $idNumber = $identificationDetail->passport_number;
                    $expireDate = $identificationDetail->passport_expiry_date;
                    break;
                case 'Drivers Licence':
                    $idNumber = $identificationDetail->licence_number;
                    $expireDate = $identificationDetail->licence_expiry_date;
                    break;
                case 'Medicare Card':
                    $idNumber = $identificationDetail->medicare_number;
                    $expireDate = $identificationDetail->medicare_card_expiry_date;
                    break;

                default:
                    # code...
                    break;
            }
        }
        return ['idNumber' => $idNumber, 'expireDate' => $expireDate];
    }

    static function getOTPEnergyData($visitor, $leadId)
    {
        $leadData = SaleProductsEnergy::with(['provider' => function ($que) {
            $que->select('id', 'name', 'user_id');
        }, 'planEnergy' => function ($que) {
            $que->select('id', 'name', 'plan_features');
        }])->select('lead_id', 'product_type', 'provider_id', 'plan_id')->where('lead_id', $leadId)->get();
        $electricityLead = $leadData->where('product_type', 1)->first();
        $gasLead = $leadData->where('product_type', 2)->first();
        $businessDetail = DB::table('sale_business_details')->select('business_name', 'business_abn')->where('lead_id', $leadId)->first();
        $journeyDetail = DB::table('lead_journey_data_energy')->select('distributor_id', 'energy_type', 'is_dual', 'moving_house', 'moving_date')->where('lead_id', $leadId)->whereNotNull('energy_type')->get();
        $journeyFirstRecord = $journeyDetail->first();
        $elecJourney = $journeyDetail->where('energy_type', 1)->first();
        $gasJourney = $journeyDetail->where('energy_type', 2)->first();
        $prepareData = self::removeGDPR((array) $visitor);
        $prepareData['business_name'] = $businessDetail ? $businessDetail->business_name : null;
        $prepareData['business_abn'] = $businessDetail ? $businessDetail->business_abn : null;
        $prepareData['is_dual'] = $journeyFirstRecord ? $journeyFirstRecord->is_dual : null;
        $prepareData['moving_house'] = $journeyFirstRecord ? $journeyFirstRecord->moving_house : null;
        $prepareData['moving_date'] = $journeyFirstRecord ? $journeyFirstRecord->moving_date : null;
        $prepareData['electricity'] = $electricityLead ? $electricityLead->toArray() : null;
        $prepareData['electricity']['energy_type'] = $elecJourney ? $elecJourney->energy_type : '';
        $prepareData['electricity']['distributor_id'] = $elecJourney ? $elecJourney->distributor_id : '';
        $prepareData['gas'] = $gasLead ? $gasLead->toArray() : null;
        if ($gasLead) {
            $prepareData['gas']['energy_type'] = $gasJourney ? $gasJourney->energy_type : '';
            $prepareData['gas']['distributor_id'] = $gasJourney ? $gasJourney->distributor_id : '';
        }

        $prepareData = self::unsetNotRequiredData($prepareData);
        return $prepareData;
    }

    static function unsetNotRequiredData($prepareData)
    {
        unset($prepareData['visitor_id']);
        unset($prepareData['plan_id']);
        unset($prepareData['provider_id']);
        unset($prepareData['electricity']['lead_id']);
        unset($prepareData['electricity']['provider_id']);
        unset($prepareData['electricity']['plan_energy']['id']);
        unset($prepareData['electricity']['plan_energy']['plan_name']);
        unset($prepareData['gas']['lead_id']);
        unset($prepareData['gas']['provider_id']);
        unset($prepareData['gas']['plan_energy']['id']);
        unset($prepareData['gas']['plan_energy']['plan_name']);
        unset($prepareData['status']);
        return $prepareData;
    }
}
