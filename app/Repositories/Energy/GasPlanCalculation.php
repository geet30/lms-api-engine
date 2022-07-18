<?php

namespace App\Repositories\Energy;


use App\Models\{Providers,EnergyPlanRate,DmoVdoPrice
};
trait GasPlanCalculation
{
   


    static function getPlanBasicInfo($plan)
    {
      
        $matched_plans['id'] = $plan['id'];
        $matched_plans['provider_id'] = $plan['provider_id'];
        $matched_plans['plan_name'] = $plan['plan_name'];
        $matched_plans['energy_type'] = $plan['energy_type'];
        $matched_plans['contract_length'] = $plan['contract_length'];
        $matched_plans['features'] = $plan['plan_features'];
        $matched_plans['green_options'] = $plan['green_options'];
        $matched_plans['green_options_desc'] = $plan['green_options_desc'];
        $matched_plans['solar_compatible'] = $plan['solar_compatible'];
       // $matched_plans['solar_desc'] = $plan['solar_desc'];
       // $matched_plans['solar_price'] = $plan['solor_price'];
        $matched_plans['benefit_term'] = $plan['benefit_term'];
        $matched_plans['credit_card_service_fee'] = $plan['credit_card_service_fee'];
        $matched_plans['counter_fee'] = $plan['counter_fee'];
        $matched_plans['paper_bill_fee'] = $plan['paper_bill_fee'];
        $matched_plans['cooling_off_period'] = $plan['cooling_off_period'];
        $matched_plans['other_fee_section'] = $plan['other_fee_section'];
        $matched_plans['plan_bonus'] = $plan['plan_bonus'];
        $matched_plans['plan_bonus_desc'] = $plan['plan_bonus_desc'];
        $matched_plans['billing_options'] = $plan['billing_options'];
        $matched_plans['payment_options'] = $plan['payment_options'];
        $matched_plans['plan_desc'] = $plan['plan_desc'];
        $matched_plans['plan_features'] = $plan['plan_features'];
        $matched_plans['view_discount'] = $plan['view_discount'];
        $matched_plans['view_benefit'] = $plan['view_benefit'];
        $matched_plans['view_bonus'] = $plan['view_bonus'];
        $matched_plans['view_contract'] = $plan['view_contract'];
        $matched_plans['view_exit_fee'] = $plan['view_exit_fee'];
        $matched_plans['why_us'] = $plan['provider']['content']['why_us'];
        $matched_plans['is_bundle_dual_plan'] = $plan['is_bundle_dual_plan'];
        $matched_plans['bundle_code'] = $plan['bundle_code'];
        $matched_plans['show_solar_plan'] = $plan['show_solar_plan'];

        return $matched_plans;
    }


    static function createPlanDocumentUrl($plan_name, $provider_name, $file_name)
    {

        if(!empty($plan_name) && !empty($provider_name) && !empty($file_name))
        {
            $disk = \Storage::disk('s3_plan');
            $url = $disk->getAdapter()->getClient()->getObjectUrl(
                \Config::get('filesystems.disks.s3_plan.bucket'),
                'Providers_Plans' . '/' . str_replace(' ', '_', $provider_name) . '/' . str_replace(' ', '_', $plan_name) . '/' . $file_name
            );
            return $url;
        }
    }
    /**
     * @param array $input
     *
     * @throws GeneralException // @phpstan-ignore-line
     *
     * @return bool
     */


        /**
     * Name: getApplyLimit()
     * Purpose: calculate limit wise charges.
     * param: $limitArray,$units
     * Date: 26-Sept-2017
     * created By: Harbrinder
     */
    static function getApplyLimit($limitArray, $units, $allLimits = null, $type = null)
    {
        $total = 0;
        if (count($limitArray) == 0) {
            if ($type == 'c1') {
                $limitArray = array_filter($allLimits, function ($value) {
                    return $value['limit_type'] == 'c2';
                });
            } elseif ($type == 'c2') {
                $limitArray = array_filter($allLimits, function ($value) {
                    return $value['limit_type'] == 'c1';
                });
            }
        }
        if (count($limitArray)) {
            foreach ($limitArray as $key => $row) {
                $limit_level[$key] = $row['limit_level'];
            }
            //sort array by level
            array_multisort($limit_level, SORT_ASC, $limitArray);
            //CALCULATE
            for ($loop = 0; $loop < count($limitArray); $loop++) {
                if ($units > 0) {
                    if ($limitArray[$loop]['limit_daily'] != "" && $limitArray[$loop]['limit_daily'] != 0 && $units > $limitArray[$loop]['limit_daily']) {
                        $usageLimitDaily = $limitArray[$loop]['limit_daily'];
                    } elseif ($limitArray[$loop]['limit_daily'] != "" && $limitArray[$loop]['limit_year'] != 0 && $units > $limitArray[$loop]['limit_year']) {
                        $usageLimitDaily = $limitArray[$loop]['limit_year'] / 365;
                    } else {
                        $usageLimitDaily = 0;
                    }
                    if ($usageLimitDaily != "" && $usageLimitDaily != 0 && $units > $usageLimitDaily) {
                        $total += $usageLimitDaily * $limitArray[$loop]['limit_charges'];
                        $units -= $usageLimitDaily;
                    } else {
                        $total += $units * $limitArray[$loop]['limit_charges'];
                        $units -= $units;
                    }
                } else
                    break;
            }
        }
        return $total;
    }
}
