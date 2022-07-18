<?php

namespace App\Repositories\Energy;


use App\Models\{Providers,EnergyPlanRate,DmoVdoPrice,PostcodeLimit
};
trait ElecPlanCalculation
{



    static function getGasPlansWithoutBill($plans, $postCode, $request,$meterType)
    {
        $units = 200;
        $expectedAnnualAdjustments = 0;
        if ($request['property_type'] == 1) {
            //get usage limits for residential
            $usageLimit = PostcodeLimit::where('post_code', $postCode)->where('usage_type', 1)->first();
        } else {
            //get usage limits for business
            $usageLimit = PostcodeLimit::where('post_code', $postCode)->where('usage_type', 2)->first();
        }
        
        if ($usageLimit) {
            $limits = $usageLimit->usageLimit;

            $usageLevel = $request['gas_usage_level'];
           
            if ($usageLevel == 1) {
                $units = $limits->gas_low_range;
            } elseif ($usageLevel == 2) {
                $units = $limits->gas_medium_range;
            } elseif ($usageLevel == 3) {
                $units = $limits->gas_high_range;
            }
           
            //get usage days
            $days = $planStaticDays = 365;
            $matchedPlans = array();
            foreach ($plans as $k => $plan) {
              
                if ($plan['rate']) {
                    //codes for which rate to use starts from here
                    $planRates = $plan['rate'];
                    $rate = [];
                    $rates = array_column($planRates, 'type');
                    $key = array_search($meterType, $rates);
                    $rate = $planRates[$key];
                    //Recurring meter charges and credit bonus
                    if (!empty($plan['recurring_meter_charges'])) {
                        $expectedAnnualAdjustments = $plan['recurring_meter_charges'];
                        $recurringMeterCharges = ($plan['recurring_meter_charges'] / $planStaticDays) * $days;
                    } else {
                        $recurringMeterCharges = 0;
                        $expectedAnnualAdjustments = 0;
                    }

                    if (!empty($plan['credit_bonus'])) {
                        $creditBonus = ($plan['credit_bonus'] / $planStaticDays) * $days;
                    } else {
                        $creditBonus = 0;
                    }
                   
                    //get matched rate code ends here
                    if (count($rate) > 0) {
                        $matchedPlans[$k] = self::getPlanBasicInfo($plan);
                        
                        $matchedPlans[$k]['days'] = $days;
                        //get provider_data
                        // $provider = self::fetchProviderData($plan['provider_id']);
                        // $matchedPlans[$k]['provider_image'] = $provider->logo;
                        // $matchedPlans[$k]['provider_name'] = $provider->name;
                        // $fullUrlPlanDocument =  self::createPlanDocumentUrl($plan['plan_name'], $provider->name, $plan['plan_document']);
                        //calculate rates
                        $peakLimits = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'peak';
                        });
                        
                        $gasCharges = self::getApplyLimit($peakLimits, $units);
                        $gasCharges = $gasCharges * $days;
                        $dailySupplyCharges = $rate['daily_supply_charges'] * $days;
                        $totalCharges = $gasCharges + $dailySupplyCharges;
                        //discount on usage
                        $discount = 0;
                        $gurrentedDiscount = 0;
                        $discountWithDual = 0;
                       
                        if ($rate['pay_day_discount_usage']) {
                            $matchedPlans[$k]['applied_pay_day_discount_usage'] = 'yes';
                            $discount = $discount + (($gasCharges * $rate['pay_day_discount_usage']) / 100);
                        }
                        if ($rate['gurrented_discount_usage']) {
                            $matchedPlans[$k]['applied_gurrented_discount_usage'] = 'yes';
                            $gurrentedDiscount += ($gasCharges * $rate['gurrented_discount_usage']) / 100;
                            $discount = $discount + (($gasCharges * $rate['gurrented_discount_usage']) / 100);
                        }
                        if ($rate['direct_debit_discount_usage']) {
                            $matchedPlans[$k]['applied_direct_debit_discount_usage'] = 'yes';
                            $discount = $discount + (($gasCharges * $rate['direct_debit_discount_usage']) / 100);
                        }
                        if ($rate['dual_fuel_discount_usage']) {
                            $matchedPlans[$k]['applied_dual_fuel_discount_usage'] = 'yes';
                            $discountWithDual = $discountWithDual + (($gasCharges * $rate['dual_fuel_discount_usage']) / 100);
                        }
                        //get supply charges
                        if ($rate['pay_day_discount_supply']) {
                            $matchedPlans[$k]['applied_pay_day_discount_supply'] = 'yes';
                            $discount = $discount + (($dailySupplyCharges * $rate['pay_day_discount_supply']) / 100);
                        }
                        if ($rate['gurrented_discount_supply']) {
                            $matchedPlans[$k]['applied_gurrented_discount_supply'] = 'yes';
                            $gurrentedDiscount += ($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100;
                            $discount = $discount + (($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100);
                        }
                        if ($rate['direct_debit_discount_supply']) {
                            $matchedPlans[$k]['applied_direct_debit_discount_supply'] = 'yes';
                            $discount = $discount + (($dailySupplyCharges * $rate['direct_debit_discount_supply']) / 100);
                        }
                        if ($rate['dual_fuel_discount_supply']) {
                            $matchedPlans[$k]['applied_dual_fuel_discount_supply'] = 'yes';
                            $discountWithDual = $discountWithDual + (($dailySupplyCharges * $rate['dual_fuel_discount_supply']) / 100);
                        }
                        $discountWithDual += $discount;
                        //bill before discount
                        $totalCharges = ($totalCharges - $gurrentedDiscount) / 100;
                        //Recurring meter charges and credit bonus
                        $totalCharges = $totalCharges + $recurringMeterCharges - $creditBonus;

                        $expectedBillAmount = ($totalCharges * $rate['gst_rate'] / 100) + $totalCharges;

                        $expectedBillAmount -= config('app.groupon_gas_subtracted_amount');
                        
                        //get total charges
                        $billAfterDiscount = (($gasCharges + $dailySupplyCharges) - $discount) / 100;
                        $billAfterDiscountWithDual = (($gasCharges + $dailySupplyCharges) - $discountWithDual) / 100;

                        //Recurring meter charges and credit bonud
                        $billAfterDiscount = $billAfterDiscount + $recurringMeterCharges - $creditBonus;
                        $billAfterDiscountWithDual = $billAfterDiscountWithDual + $recurringMeterCharges - $creditBonus;

                        //get gst charges
                        $discountedBillAmount = ($billAfterDiscount * $rate['gst_rate'] / 100) + $billAfterDiscount;
                        $discountedBillAmountWithDual = ($billAfterDiscountWithDual * $rate['gst_rate'] / 100) + $billAfterDiscountWithDual;

                        $discountedBillAmount -= config('app.groupon_gas_subtracted_amount');
                        $discountedBillAmountWithDual -= config('app.groupon_gas_subtracted_amount');
                       

                        $expectedBillAmount = $expectedBillAmount;
                        $monthlyExpectedBillAmount = ($expectedBillAmount / $days) * 31;
                        $quaterlyExpectedBillAmount = ($expectedBillAmount / $days) * 91;
                        $annuallyExpectedBillAmount = ($expectedBillAmount / $days) * 365;

                        $discountedBillAmount = $discountedBillAmount;
                        $monthlyDiscountedBillAmount = ($discountedBillAmount / $days) * 31;
                        $quaterlyDiscountedBillAmount = ($discountedBillAmount / $days) * 91;
                        $annuallyDiscountedBillAmount = ($discountedBillAmount / $days) * 365;

                        $discountedBillWithDual = $discountedBillAmountWithDual;
                        $monthlyDiscountedBillWithDual = ($discountedBillWithDual / $days) * 31;
                        $quaterlyDiscountedBillWithDual = ($discountedBillWithDual / $days) * 91;
                        $annuallyDiscountedBillWithDual = ($discountedBillWithDual / $days) * 365;
                       
                        $matchedPlans[$k]['expected_gas_bill_amount'] = ceil(round($expectedBillAmount, 2));
                       
                        $matchedPlans[$k]['expected_monthly_gas_bill_amount'] = ceil(round($monthlyExpectedBillAmount, 2));
        
                        $matchedPlans[$k]['expected_quaterly_gas_bill_amount'] = ceil(round($quaterlyExpectedBillAmount, 2));
                       
                        $matchedPlans[$k]['expected_annually_gas_bill_amount'] = ceil(round($annuallyExpectedBillAmount, 2));
                      
                        $matchedPlans[$k]['expected_discounted_gas_bill_amount'] = ceil(round($discountedBillAmount, 2));
                       
                        $matchedPlans[$k]['expected_monthly_discounted_gas_bill_amount'] = ceil(round($monthlyDiscountedBillAmount, 2));
                       
                        $matchedPlans[$k]['expected_quaterly_discounted_gas_bill_amount'] = ceil(round($quaterlyDiscountedBillAmount, 2));
                      
                        $matchedPlans[$k]['expected_annually_discounted_gas_bill_amount'] = ceil(round($annuallyDiscountedBillAmount, 2));
                       
                        $matchedPlans[$k]['expected_discounted_amount_with_duel'] = ceil(round($discountedBillAmountWithDual, 2));
                       
                        $matchedPlans[$k]['expected_monthly_discounted_amount_with_duel'] = ceil(round($monthlyDiscountedBillWithDual, 2));
                       

                        $matchedPlans[$k]['expected_quaterly_discounted_amount_with_duel'] = ceil(round($quaterlyDiscountedBillWithDual, 2));
                        
                        $matchedPlans[$k]['expected_annually_discounted_amount_with_duel'] = ceil(round($annuallyDiscountedBillWithDual, 2));

                        $matchedPlans[$k]['expected_annual_adjustments'] = $expectedAnnualAdjustments;
                        $matchedPlans[$k]['daily_usage_limit'] = $units;
                        //$matchedPlans[$k]['provider'] = $plan['provider'];
                        
                        $matchedPlans[$k]['terms_condition'] = $plan['terms_condition'];
                      //  $matchedPlans[$k]['plan_document'] = $fullUrlPlanDocument;
                        $matchedPlans[$k]['rates'] = $plan['rate'];
                        $matchedPlans[$k]['plan_tags'] = $plan['get_plan_tags'];
                        $matchedPlans[$k]['pay_day_discount_usage'] = $rate['pay_day_discount_usage'];
                        $matchedPlans[$k]['gurrented_discount_usage'] = $rate['gurrented_discount_usage'];
                        $matchedPlans[$k]['exit_fee_option'] = $rate['exit_fee_option'];
                        $matchedPlans[$k]['exit_fee'] = $rate['exit_fee'];
                        $matchedPlans[$k]['show_desc'] = 'Approx. charges (incl GST) for ' . $days . ' days based on average ' . sprintf("%.2f", $units) . ' Mj daily usage.';
                        $matchedPlans[$k]['show_monthly_desc'] = 'Approx. charges (incl GST) for 31 days based on average ' . sprintf("%.2f", $units) . ' Mj daily usage.';
                        $matchedPlans[$k]['show_quaterly_desc'] = 'Approx. charges (incl GST) for 91 days based on average ' . sprintf("%.2f", $units) . ' Mj daily usage.';
                        $matchedPlans[$k]['show_annually_desc'] = 'Approx. charges (incl GST) for 365 days based on average ' . sprintf("%.2f", $units) . ' Mj daily usage.';
                        $matchedPlans[$k]['dual_only'] = $plan['dual_only'];
                    }
                }
            }
            
            return $matchedPlans;
        }
    }

    static function getGasPlansWithBill($plans, $request,$postCode, $meterType)
    {
        $totalOneDayUsage = 0;
       
        $billStartDate = $request['gas_bill_startdate'];
        $billEndDate = $request['gas_bill_enddate'];
        $getDaysCount = getDaysDiff($billStartDate, $billEndDate);
        $peakUsage = $request['gas_peak_usage'];
        $offpeak_usage = $request['gas_off_peak_usage'];
        if (isset($request['gas_bill_amount']))
            $gas_bill_amount = $request['gas_bill_amount'];
        else
            $gas_bill_amount = '';

        $dailyPeakUsage = $peakUsage / $getDaysCount;
        $daily_offpeak_usage = $offpeak_usage / $getDaysCount;
        $totalOneDayUsage = $dailyPeakUsage + $daily_offpeak_usage;
       
        $expectedAnnualAdjustments = 0;
        $matchedPlans = array();
        $planStaticDays = config('app.plan_static_days');

        foreach ($plans as $k => $plan) {
            if ($plan['rate']) {
                //codes for which rate to use starts from here
                $planRates = $plan['rate'];
                $rate = [];
                $rates = array_column($planRates, 'type');
                $key = array_search($meterType, $rates);
                $rate = $planRates[$key];
               

                //get recurring_meter_charges && credit bonus

                if (!empty($plan['recurring_meter_charges'])) {
                    $expectedAnnualAdjustments = $plan['recurring_meter_charges'];
                    $recurringMeterCharges = ($plan['recurring_meter_charges'] / $planStaticDays) * $getDaysCount;
                } else {
                    $recurringMeterCharges = 0;
                    $expectedAnnualAdjustments = 0;
                }

                if (!empty($plan['credit_bonus'])) {
                    $creditBonus = ($plan['credit_bonus'] / $planStaticDays) * $getDaysCount;
                } else {
                    $creditBonus = 0;
                }
                
                //get matched rate code ends here
                if (count($rate) > 0) {
                    $matchedPlans[$k] = self::getPlanBasicInfo($plan);
                    $matchedPlans[$k]['days'] = $getDaysCount;
                    //get provider_data
                    //$provider = self::fetchProviderData($plan['provider_id']);
                    //$matchedPlans[$k]['provider_image'] = $provider->logo;
                    //$matchedPlans[$k]['provider_name'] = $provider->name;
                    //$fullUrlPlanDocument =  self::createPlanDocumentUrl($plan['plan_name'], $provider->name, $plan['plan_document']);
                    //get peak charges
                    $peakLimits = array_filter($rate['plan_rate_limit'], function ($value) {
                        return $value['limit_type'] == 'peak';
                    });
                    $peak_charges = self::getApplyLimit($peakLimits, $dailyPeakUsage);
                    //get off peak amount
                    $offpeak_limits = array_filter($rate['plan_rate_limit'], function ($value) {
                        return $value['limit_type'] == 'offpeak';
                    });
                    $offpeak_charges = self::getApplyLimit($offpeak_limits, $daily_offpeak_usage);
                    //usage_charges
                    $one_day_charges = ($peak_charges + $offpeak_charges + $rate['daily_supply_charges']) / 100;
                    $totalCharges = $one_day_charges * $getDaysCount;
                    //get discounted bill
                    //discount on usage
                    $discount = 0;
                    $gurrentedDiscount = 0;
                    $discountWithDual = 0;
                    $total_usage_charges = $peak_charges + $offpeak_charges;
                    //$total_usage_charges = ($peak_charges + $offpeak_charges)* $getDaysCount/100;


                    if ($rate['pay_day_discount_usage']) {
                        $matchedPlans[$k]['applied_pay_day_discount_usage'] = 'yes';
                        $discount = $discount + (($total_usage_charges * $rate['pay_day_discount_usage']) / 100);
                    }
                    if ($rate['gurrented_discount_usage']) {
                        $new_charge = ($total_usage_charges * $getDaysCount) / 100;
                        $matchedPlans[$k]['applied_gurrented_discount_usage'] = 'yes';
                        $gurrentedDiscount += ($new_charge * $rate['gurrented_discount_usage']) / 100;
                        $discount = $discount + (($total_usage_charges * $rate['gurrented_discount_usage']) / 100);
                    }
                    if ($rate['direct_debit_discount_usage']) {
                        $matchedPlans[$k]['applied_direct_debit_discount_usage'] = 'yes';
                        $discount = $discount + (($total_usage_charges * $rate['direct_debit_discount_usage']) / 100);
                    }
                    if ($rate['dual_fuel_discount_usage']) {
                        $matchedPlans[$k]['applied_dual_fuel_discount_usage'] = 'yes';
                        $discountWithDual = $discountWithDual + (($total_usage_charges * $rate['dual_fuel_discount_usage']) / 100);
                    }
                    $discountWithDual += $discount;
                    $total_usage_charges_without_dual = $total_usage_charges - $discount;
                    $total_usage_charges_with_dual = $total_usage_charges - $discountWithDual;
                    $total_usage_charges_without_dual = $total_usage_charges_without_dual * $getDaysCount;
                    $total_usage_charges_with_dual = $total_usage_charges_with_dual * $getDaysCount;
                    //discount on supply
                    $dailySupplyCharges = $rate['daily_supply_charges'] * $getDaysCount;
                    $discount = 0;
                    $discountWithDual = 0;
                    if ($rate['pay_day_discount_supply']) {
                        $matchedPlans[$k]['applied_pay_day_discount_supply'] = 'yes';
                        $discount = $discount + (($dailySupplyCharges * $rate['pay_day_discount_supply']) / 100);
                    }
                    if ($rate['gurrented_discount_supply']) {
                        $supply_new_charge = ($dailySupplyCharges) / 100;
                        $matchedPlans[$k]['applied_gurrented_discount_supply'] = 'yes';
                        $gurrentedDiscount += ($supply_new_charge * $rate['gurrented_discount_supply']) / 100;
                        //$gurrentedDiscount += ($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100;
                        $discount = $discount + (($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100);
                    }
                    if ($rate['direct_debit_discount_supply']) {
                        $matchedPlans[$k]['applied_direct_debit_discount_supply'] = 'yes';
                        $discount = $discount + (($dailySupplyCharges * $rate['direct_debit_discount_supply']) / 100);
                    }
                    if ($rate['dual_fuel_discount_supply']) {
                        $matchedPlans[$k]['applied_dual_fuel_discount_supply'] = 'yes';
                        $discountWithDual = $discountWithDual + (($dailySupplyCharges * $rate['dual_fuel_discount_supply']) / 100);
                    }
                    //bill before discount
                    $discountWithDual += $discount;
                    /*$gurrentedDiscount = $totalCharges - $gurrentedDiscount;*/

                    $totalCharges = $totalCharges - $gurrentedDiscount;

                    //Recurring meter charges and credit bonus
                    $totalCharges = $totalCharges + $recurringMeterCharges - $creditBonus;

                    $expectedBillAmount = ($totalCharges * $rate['gst_rate'] / 100) + $totalCharges;

                    $expectedBillAmount -= config('app.groupon_gas_subtracted_amount');
                    

                    $dailySupplyCharges_without_dual = $dailySupplyCharges - $discount;
                    $dailySupplyCharges_with_dual = $dailySupplyCharges - $discountWithDual;


                    $billAfterDiscount_without_dual = ($total_usage_charges_without_dual + $dailySupplyCharges_without_dual) / 100;
                    $billAfterDiscountWithDual = ($total_usage_charges_with_dual + $dailySupplyCharges_with_dual) / 100;

                    /* Recurring meter charges and credit bonus */
                    $billAfterDiscount_without_dual = $billAfterDiscount_without_dual + $recurringMeterCharges - $creditBonus;
                    $billAfterDiscountWithDual = $billAfterDiscountWithDual + $recurringMeterCharges - $creditBonus;
                    /* end here */

                    $discountedBillAmount = ($billAfterDiscount_without_dual * $rate['gst_rate'] / 100) + $billAfterDiscount_without_dual;
                    $discountedBillWithDual = ($billAfterDiscountWithDual * $rate['gst_rate'] / 100) + $billAfterDiscountWithDual;

                    $discountedBillAmount -= config('app.groupon_gas_subtracted_amount');
                    $discountedBillWithDual -= config('app.groupon_gas_subtracted_amount');
                    

                    $expectedBillAmount = $expectedBillAmount;
                    $monthlyExpectedBillAmount = ($expectedBillAmount / $getDaysCount) * 31;
                    $quaterlyExpectedBillAmount = ($expectedBillAmount / $getDaysCount) * 91;
                    $annuallyExpectedBillAmount = ($expectedBillAmount / $getDaysCount) * 365;

                    $discountedBillAmount = $discountedBillAmount;
                    $monthlyDiscountedBillAmount = ($discountedBillAmount / $getDaysCount) * 31;
                    $quaterlyDiscountedBillAmount = ($discountedBillAmount / $getDaysCount) * 91;
                    $annuallyDiscountedBillAmount = ($discountedBillAmount / $getDaysCount) * 365;

                    $discountedBillWithDual = $discountedBillWithDual;
                    $monthlyDiscountedBillWithDual = ($discountedBillWithDual / $getDaysCount) * 31;
                    $quaterlyDiscountedBillWithDual = ($discountedBillWithDual / $getDaysCount) * 91;
                    $annuallyDiscountedBillWithDual = ($discountedBillWithDual / $getDaysCount) * 365;

                    $matchedPlans[$k]['expected_gas_bill_amount'] = ceil(round($expectedBillAmount, 2));
                  


                    $matchedPlans[$k]['expected_monthly_gas_bill_amount'] = ceil(round($monthlyExpectedBillAmount, 2));
                   

                    $matchedPlans[$k]['expected_quaterly_gas_bill_amount'] = ceil(round($quaterlyExpectedBillAmount, 2));
                   

                    $matchedPlans[$k]['expected_annually_gas_bill_amount'] = ceil(round($annuallyExpectedBillAmount, 2));
                   
                    $matchedPlans[$k]['expected_discounted_gas_bill_amount'] = ceil(round($discountedBillAmount, 2));
                    

                    $matchedPlans[$k]['expected_monthly_discounted_gas_bill_amount'] = ceil(round($monthlyDiscountedBillAmount, 2));
                   

                    $matchedPlans[$k]['expected_quaterly_discounted_gas_bill_amount'] = ceil(round($quaterlyDiscountedBillAmount, 2));
                   

                    $matchedPlans[$k]['expected_annually_discounted_gas_bill_amount'] = ceil(round($annuallyDiscountedBillAmount, 2));
                    

                    $matchedPlans[$k]['expected_discounted_amount_with_duel'] = ceil(round($discountedBillWithDual, 2));
                   

                    $matchedPlans[$k]['expected_monthly_discounted_amount_with_duel'] = ceil(round($monthlyDiscountedBillWithDual, 2));
                   


                    $matchedPlans[$k]['expected_quaterly_discounted_amount_with_duel'] = ceil(round($quaterlyDiscountedBillWithDual, 2));
                   

                    $matchedPlans[$k]['expected_annually_discounted_amount_with_duel'] = ceil(round($annuallyDiscountedBillWithDual, 2));

                   

                    $matchedPlans[$k]['expected_annual_adjustments'] = $expectedAnnualAdjustments;

                    //$matchedPlans[$k]['provider'] = $plan['provider'];
                    $matchedPlans[$k]['terms_condition'] = $plan['terms_condition'];
                    $matchedPlans[$k]['pay_day_discount_usage'] = $rate['pay_day_discount_usage'];
                    $matchedPlans[$k]['rates'] = $plan['rate'];
                    $matchedPlans[$k]['plan_tags'] = $plan['get_plan_tags'];
                    $matchedPlans[$k]['gurrented_discount_usage'] = $rate['gurrented_discount_usage'];
                    $matchedPlans[$k]['exit_fee'] = $rate['exit_fee'];
                   // $matchedPlans[$k]['plan_document'] = $fullUrlPlanDocument;
                    $matchedPlans[$k]['show_desc'] = 'Approx. charges (incl GST) for ' . $getDaysCount . ' days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' Mj daily usage.';
                    $matchedPlans[$k]['show_monthly_desc'] = 'Approx. charges (incl GST) for 31 days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' Mj daily usage.';
                    $matchedPlans[$k]['show_quaterly_desc'] = 'Approx. charges (incl GST) for 91 days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' Mj daily usage.';
                    $matchedPlans[$k]['show_annually_desc'] = 'Approx. charges (incl GST) for 365 days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' Mj daily usage.';
                    $matchedPlans[$k]['dual_only'] = $plan['dual_only'];

                    if (isset($gas_bill_amount) && !empty($gas_bill_amount)) {
                        $gas_bill_amount_calc = $gas_bill_amount - $matchedPlans[$k]['expected_gas_bill_amount'];
                        if ($gas_bill_amount_calc <= 0) {
                            $matchedPlans[$k]['savings_bill_amount_gas'] = "No savings";
                            $matchedPlans[$k]['savings_monthly_bill_amount_gas'] = 'No savings';
                            $matchedPlans[$k]['savings_annually_bill_amount_gas'] = 'No savings';
                            $matchedPlans[$k]['savings_quaterly_bill_amount_gas'] = 'No savings';
                        } else {
                            $savings_bill_amount = $gas_bill_amount_calc;

                            $savings_monthly_bill_amount = ($gas_bill_amount_calc / $getDaysCount) * 31;
                            $savings_quaterly_bill_amount = ($gas_bill_amount_calc / $getDaysCount) * 91;
                            $savings_annually_bill_amount = ($gas_bill_amount_calc / $getDaysCount) * 365;

                            $matchedPlans[$k]['savings_bill_amount_gas'] = round($savings_bill_amount, 2);
                            $matchedPlans[$k]['savings_monthly_bill_amount_gas'] = round($savings_monthly_bill_amount, 2);
                            $matchedPlans[$k]['savings_quaterly_bill_amount_gas'] = round($savings_quaterly_bill_amount, 2);
                            $matchedPlans[$k]['savings_annually_bill_amount_gas'] = round($savings_annually_bill_amount, 2);
                        }
                    } else {
                        $matchedPlans[$k]['savings_bill_amount_gas'] = '';
                        $matchedPlans[$k]['savings_monthly_bill_amount_gas'] = '';
                        $matchedPlans[$k]['savings_annually_bill_amount_gas'] = '';
                        $matchedPlans[$k]['savings_quaterly_bill_amount_gas'] = '';
                    }
                }
            }
        }
        return $matchedPlans;
    }
    static function getElectricityPlansWithoutBill($plans, $postCode, $request, $meterType)
    {
        $dmo_postcode = explode(',', $request['post_code']);

        $tariffType = "";
        $dmoVdoType = 0;
        if (trim($postCode[2]) == "VIC") {
            $dmoVdoType = 1;
        }
            $usageLimit = DmoVdoPrice::where(['property_type' => $request['property_type'], 'distributor_id' => $request['elec_distributor_id'], 'tariff_type' => 'peak_only', 'offer_type' => $dmoVdoType])->first();
            $tariffType = "peak_only";
           
        if ($usageLimit) {
           // $planStaticDays = isset(config('energyPlan.plan_static_days'))? config('energyPlan.plan_static_days'):365;
            $planStaticDays = 365;
            $yearlyUsage = $usageLimit->peak_only;
            $units = $yearlyUsage /$planStaticDays;
           
            $recurringMeterCharges = 0;
            $creditBonus = 0;
            $matchedPlans = array();
            
            foreach ($plans as $k => $plan) {
               
                if ($plan['rate']) {
                    //codes for which rate to use starts from here
                    $planRates = $plan['rate'];
                    $rate = [];
                    $rates = array_column($plan['rate'], 'type');
                    $key = array_search($meterType, $rates);
                    $rate = $planRates[$key];
                   
                    //Recurring meter charges and credit bonus
                    $recurringMeterCharges = !empty($plan['recurring_meter_charges']) ? $plan['recurring_meter_charges'] : 0;

                    $creditBonus = !empty($plan['credit_bonus']) ? $plan['credit_bonus'] : 0;

                    //end here

                    //get matched rate code ends here
                    if (count($rate) > 0) {
                        $matchedPlans[$k] = self::getPlanBasicInfo($plan);
                        
                        //days 91 static for electricity assumed
                        $matchedPlans[$k]['days'] = $planStaticDays;
                       
                       
                        //$fullUrlPlanDocument =  self::createPlanDocumentUrl($plan['plan_name'], $plan['provider']['name'], $plan['plan_document']);
                       
                        //calculate rates
                        //get one day charges
                        $peakLimits = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'peak';
                        });
                        $onedayCharges = self::getApplyLimit($peakLimits, $units);
                        $electricityCharges = $onedayCharges * config('app.plan_static_days');
                        $dailySupplyCharges = $rate['daily_supply_charges'] * config('app.plan_static_days');
                        //discounted bill amount
                        //discount on usage
                        $discount = 0;
                        $discountWithDuelFuel = 0;
                        $gurrentedDiscount = 0;
                        if ($rate['pay_day_discount_usage']) {
                            $matchedPlans[$k]['applied_pay_day_discount_usage'] = 'yes';
                            $discount = $discount + (($electricityCharges * $rate['pay_day_discount_usage']) / 100);
                        }
                        if ($rate['gurrented_discount_usage']) {
                            $matchedPlans[$k]['applied_gurrented_discount_usage'] = 'yes';
                            $gurrentedDiscount += ($electricityCharges * $rate['gurrented_discount_usage']) / 100;
                            $discount = $discount + (($electricityCharges * $rate['gurrented_discount_usage']) / 100);
                        }
                        if ($rate['direct_debit_discount_usage']) {
                            $matchedPlans[$k]['applied_direct_debit_discount_usage'] = 'yes';
                            $discount = $discount + (($electricityCharges * $rate['direct_debit_discount_usage']) / 100);
                        }
                        if ($rate['dual_fuel_discount_usage']) {
                            $matchedPlans[$k]['applied_dual_fuel_discount_usage'] = 'yes';
                            $discountWithDuelFuel = $discountWithDuelFuel + (($electricityCharges * $rate['dual_fuel_discount_usage']) / 100);
                        }
                        //get supply charges
                        if ($rate['pay_day_discount_supply']) {
                            $matchedPlans[$k]['applied_pay_day_discount_supply'] = 'yes';
                            $discount = $discount + (($dailySupplyCharges * $rate['pay_day_discount_supply']) / 100);
                        }
                        if ($rate['gurrented_discount_supply']) {
                            $matchedPlans[$k]['applied_gurrented_discount_supply'] = 'yes';
                            $gurrentedDiscount += ($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100;
                            $discount = $discount + (($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100);
                        }
                        if ($rate['direct_debit_discount_supply']) {
                            $matchedPlans[$k]['applied_direct_debit_discount_supply'] = 'yes';
                            $discount = $discount + (($dailySupplyCharges * $rate['direct_debit_discount_supply']) / 100);
                        }
                        if ($rate['dual_fuel_discount_supply']) {
                            $matchedPlans[$k]['applied_dual_fuel_discount_supply'] = 'yes';
                            $discountWithDuelFuel = $discountWithDuelFuel + (($dailySupplyCharges * $rate['dual_fuel_discount_supply']) / 100);
                        }
                        $discountWithDuelFuel += $discount;
                        $billOnDiscount = (($electricityCharges + $dailySupplyCharges) - $gurrentedDiscount) / 100;

                        //Recurring meter charges and credit bonus
                        $billBeforeDiscount = $billOnDiscount + $recurringMeterCharges - $creditBonus;

                        $expectedBillAmount = ($billBeforeDiscount * $rate['gst_rate'] / 100) + $billBeforeDiscount;

                        $expectedBillAmount -= config('app.groupon_electricity_subtracted_amount');
                        

                        $billOnAfterDiscount = (($electricityCharges + $dailySupplyCharges) - $discount) / 100;
                        $bill_on_after_duel_dis = (($electricityCharges + $dailySupplyCharges) - $discountWithDuelFuel) / 100;

                        //Recurring meter charges and credit bonus
                        $billAfterDiscount = $billOnAfterDiscount + $recurringMeterCharges - $creditBonus;
                        $billAfterDuelDis = $bill_on_after_duel_dis + $recurringMeterCharges - $creditBonus;

                        //get gst charges
                        $discountedBillAmount = ($billAfterDiscount * $rate['gst_rate'] / 100) + $billAfterDiscount;
                        $discountedBillAfterDuel = ($billAfterDuelDis * $rate['gst_rate'] / 100) + $billAfterDuelDis;

                        $discountedBillAmount -= config('app.groupon_electricity_subtracted_amount');
                        $discountedBillAfterDuel -= config('app.groupon_electricity_subtracted_amount');
                        
                        $totalDays = $planStaticDays;
                        $expectedBillAmount = $expectedBillAmount;
                        $monthlyExpectedBillAmount = ($expectedBillAmount / $totalDays) * 31;
                        $quaterlyExpectedBillAmount = ($expectedBillAmount / $totalDays) * 91;
                        $annuallyExpectedBillAmount = ($expectedBillAmount / $totalDays) * 365;

                        $discountedBillAmount = $discountedBillAmount;
                        $monthlyDiscountedBillAmount = ($discountedBillAmount / $totalDays) * 31;
                        $quaterlyDiscountedBillAmount = ($discountedBillAmount / $totalDays) * 91;
                        $annuallyBiscountedBillAmount = ($discountedBillAmount / $totalDays) * 365;

                        $discountedBillWithDual = $discountedBillAfterDuel;
                        $monthlyDiscountedBillWithDual = ($discountedBillWithDual / $totalDays) * 31;
                        $quaterlyDiscountedBillWithDual = ($discountedBillWithDual / $totalDays) * 91;
                        $annuallyDiscountedBillWithDual = ($discountedBillWithDual / $totalDays) * 365;

                        $matchedPlans[$k]['expected_bill_amount'] = ceil(round($expectedBillAmount, 2));
                     
                        $matchedPlans[$k]['expected_quaterly_bill_amount'] = ceil(round($quaterlyExpectedBillAmount, 2));
                     
                        $matchedPlans[$k]['expected_discounted_bill_amount'] = ceil(round($discountedBillAmount, 2));
                      
                        $matchedPlans[$k]['expected_monthly_discounted_bill_amount'] = ceil(round($monthlyDiscountedBillAmount, 2));
                        
                        $matchedPlans[$k]['expected_quaterly_discounted_bill_amount'] = ceil(round($quaterlyDiscountedBillAmount, 2));
                      
                        $matchedPlans[$k]['expected_annually_discounted_bill_amount'] = ceil(round($annuallyBiscountedBillAmount, 2));
                        
                        $matchedPlans[$k]['expected_discounted_amount_with_duel'] = ceil(round($discountedBillAfterDuel, 2));

                        $matchedPlans[$k]['expected_monthly_discounted_amount_with_duel'] = ceil(round($monthlyDiscountedBillWithDual, 2));
                       
                        $matchedPlans[$k]['expected_quaterly_discounted_amount_with_duel'] = ceil(round($quaterlyDiscountedBillWithDual, 2));
                        
                        $matchedPlans[$k]['expected_annually_discounted_amount_with_duel'] = ceil(round($annuallyDiscountedBillWithDual, 2));

                        $matchedPlans[$k]['expected_annual_adjustments'] = $recurringMeterCharges;

                        $matchedPlans[$k]['daily_usage_limit'] = $units;
                        $matchedPlans[$k]['pay_day_discount_usage'] = $rate['pay_day_discount_usage'];
                        $matchedPlans[$k]['gurrented_discount_usage'] = $rate['gurrented_discount_usage'];
                        $matchedPlans[$k]['exit_fee_option'] = $rate['exit_fee_option'];
                        $matchedPlans[$k]['exit_fee'] = $rate['exit_fee'];
                      
                        $matchedPlans[$k]['terms_condition'] = $plan['terms_condition'];
                        //$matchedPlans[$k]['plan_document'] = $fullUrlPlanDocument;
                        $matchedPlans[$k]['solar_rates'] = $plan['plan_solar_rate'];
                       if (isset($plan['plan_solar_rate_normal']['solar_price'])) {
                        $matchedPlans[$k]['plan_solar_rate_normal'] = $plan['plan_solar_rate_normal']['solar_price'];
                    } else {
                        $matchedPlans[$k]['solar_rate_normal'] = 0;
                    }
                    if (isset($plan['plan_solar_rate_permimum']['solar_price'])) {
                        $matchedPlans[$k]['plan_solar_rate_permimum'] = $plan['plan_solar_rate_permimum']['solar_price'];
                    } else {
                        $matchedPlans[$k]['plan_solar_rate_permimum'] = 0;
                    }
                       
                        $matchedPlans[$k]['rates'] = $plan['rate'];
                       // $matchedPlans[$k]['plan_tags'] = $plan['plan_tags'];
                        $matchedPlans[$k]['show_desc'] = 'Approx. charges (incl GST) for ' . config('app.plan_static_days') . ' days based on average ' . sprintf("%.2f", $units) . '  kWh daily estimated usage.';
                        $matchedPlans[$k]['show_monthly_desc'] = 'Approx. charges (incl GST) for 31 days based on average ' . sprintf("%.2f", $units) . ' kWh daily usage.';
                        $matchedPlans[$k]['show_quaterly_desc'] = 'Approx. charges (incl GST) for 91 days based on average ' . sprintf("%.2f", $units) . ' kWh daily usage.';
                        $matchedPlans[$k]['show_annually_desc'] = 'Approx. charges (incl GST) for 365 days based on average ' . sprintf("%.2f", $units) . ' kWh daily usage.';

                        $matchedPlans[$k]["tariff_type"] = $tariffType;
                        $matchedPlans[$k]['dual_only'] = $plan['dual_only'];
                    }
                }
            }
           
            return $matchedPlans;
        } else {
            $matchedPlans = [];
            return $matchedPlans;
        }
    }


    static function getElectricityPlansWithBill($plans, $postcode, $request, $meterType){

        $matchedPlans = [];
       
        $billStartDate = $request['electricity_bill_startdate'];
        $billEndDate = $request['electricity_bill_enddate'];
       
        $getDaysCount = getDaysDiff($billStartDate, $billEndDate);
       
        $controlLoadOneUsage = '';
        $controlLoadTwoUsage = '';
        $controlLoadOneOffPeak = '';
        $controlLoadOneShoulder = '';
        $controlLoadTwoOffPeak = '';
        $controlLoadTwoShoulder = '';

        $totalOneDayUsage = 0;
        if(isset($request['electricity_bill_amount']))
            $electricityBillAmount = $request['electricity_bill_amount'];
        else
            $electricityBillAmount = '';

        //$electricityBillAmount  = 129.2512;
        $planStaticDays = config('app.plan_static_days');
        $monthlyDiscountedBillAmount = 0;
        $quaterlyDiscountedBillAmount = 0;
        $annuallyDiscountedBillAmount = 0;
        $expectedAnnualAdjustments = 0;     
        $request['elec_metertype']  = $request['meter_type'];
        if ($request['meter_type'] == 'peak') {
            $peakUsage = $request['electricity_peak_usage'];
            $dailyPeakUsage = $peakUsage / $getDaysCount;
            $totalOneDayUsage += $dailyPeakUsage;
            $controlLoadOneUsage = $request['control_load_one_usage'];
            $controlLoadTwoUsage = $request['control_load_two_usage'];
        } elseif ($request['meter_type'] == 'double') {
            $peakUsage = $request['electricity_peak_usage'];
            $dailyPeakUsage = $peakUsage / $getDaysCount;
            $controlLoadOneUsage = $request['control_load_one_usage'];
            $controlLoadTwoUsage = $request['control_load_two_usage'];

            $controlLoadOneOffPeak = $request['control_load_one_off_peak'];
            $controlLoadTwoOffPeak = $request['control_load_two_off_peak'];
            $controlLoadOneShoulder = $request['control_load_one_shoulder'];
            $controlLoadTwoShoulder = $request['control_load_two_shoulder'];

            $totalOneDayUsage += $dailyPeakUsage + ($controlLoadOneUsage / $getDaysCount) + ($controlLoadTwoUsage / $getDaysCount)+ ($controlLoadOneOffPeak/$getDaysCount) + ($controlLoadTwoOffPeak/$getDaysCount) + ( $controlLoadOneShoulder/$getDaysCount) + ($controlLoadTwoShoulder/$getDaysCount);
        } elseif ($request['meter_type'] == 'timeofuse') {
            $peakUsage = $request['electricity_peak_usage'];
            $offpeak_usage = $request['shoulder_offpeak_usage'];
            $shoulder_usage = $request['shoulder_timeofuse_usage'];
            $dailyPeakUsage = $peakUsage / $getDaysCount;
            $daily_off_peak_usage = $offpeak_usage / $getDaysCount;
            $daily_shoulder_usage = $shoulder_usage / $getDaysCount;
            $controlLoadOneUsage = $request['control_load_one_usage'];
            $controlLoadTwoUsage = $request['control_load_two_usage'];

            $controlLoadOneOffPeak = $request['control_load_one_off_peak'];
            $controlLoadTwoOffPeak = $request['control_load_two_off_peak'];
            $controlLoadOneShoulder = $request['control_load_one_shoulder'];
            $controlLoadTwoShoulder = $request['control_load_two_shoulder'];

            $totalOneDayUsage += $dailyPeakUsage + ($offpeak_usage / $getDaysCount) + ($shoulder_usage / $getDaysCount) + ($controlLoadOneUsage / $getDaysCount) + ($controlLoadTwoUsage / $getDaysCount)+($controlLoadOneOffPeak/$getDaysCount) + ($controlLoadTwoOffPeak/$getDaysCount) + ( $controlLoadOneShoulder/$getDaysCount) + ($controlLoadTwoShoulder/$getDaysCount)+ ($controlLoadOneOffPeak/$getDaysCount)*31 + ($controlLoadTwoOffPeak/$getDaysCount)*31 + ( $controlLoadOneShoulder/$getDaysCount)*31 + ($controlLoadTwoShoulder/$getDaysCount)*31;;
        }

        foreach ($plans as $k => $plan) {
            if (!empty($plan['rate'])) {
                $planRates = $plan['rate'];
                $rate = [];
                $rates = array_column($planRates, 'type');
                    $key = array_search($meterType, $rates);
                    $rate = $planRates[$key];
                

                //Check rate type
                if (count($rate) > 0 && ($rate['type'] == 'timeofuse_only' || $rate['type'] == 'timeofuse_c1' || $rate['type'] == 'timeofuse_c2' || $rate['type'] == 'timeofuse_c1_c2') && $shoulder_usage != 0) {
                    //Call Shoulder rate method
                    $rate = self::getShoulderRates($planRates, $rate);
                }

                // Recurring meter chnarges and credit bonus

                if (!empty($plan['recurring_meter_charges'])) {
                    $expectedAnnualAdjustments = $plan['recurring_meter_charges'];
                    $recurringMeterCharges = ($plan['recurring_meter_charges'] / $planStaticDays) * $getDaysCount;
                } else {
                    $recurringMeterCharges = 0;
                    $expectedAnnualAdjustments = 0;
                }

                if (!empty($plan['credit_bonus'])) {
                    $creditBonus = ($plan['credit_bonus'] / $planStaticDays) * $getDaysCount;
                } else {
                    $creditBonus = 0;
                }

                //get matched rate code ends here
                if (count($rate) > 0) {
                    $controlLoad1SupplyCharges = 0;
                    $controlLoad2SupplyCharges = 0;
                    $controlLoad1OnedayCharges = 0;
                    $controlLoad2OnedayCharges = 0;
                    $controlLoadTwoOffPeakCharge = 0;
                    $controlLoadTwoShoulderCharge = 0;
                    $controlLoadOneShoulderCharge = 0;
                    $controlLoadOneOffPeakCharge = 0;
                    $matchedPlans[$k] = self::getPlanBasicInfo($plan);
                    $matchedPlans[$k]['days'] = $getDaysCount;
                    //get provider_data
                    // $provider = self::fetchProviderData($plan['provider_id']);
                    // $matchedPlans[$k]['provider_image'] = $provider->logo;
                    // $matchedPlans[$k]['provider_name'] = $provider->name;
                    // $fullUrlPlanDocument =  self::createPlanDocumentUrl($plan['plan_name'], $provider->name, $plan['plan_document']);
                    //get peak charges
                    $peakLimits = array_filter($rate['plan_rate_limit'], function ($value) {
                        return $value['limit_type'] == 'peak';
                    });
                    $peak_charges = self::getApplyLimit($peakLimits, $dailyPeakUsage);
                    //get control load price
                    
                    //calculate c1 charges here if meter type double
                    if ($request['elec_metertype'] == 'double' && $controlLoadOneUsage && !empty($control_load_one_usage)) {
                        $control_load_1_oneday_usage = $controlLoadOneUsage / $getDaysCount;
                        $c1_limits = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'c1';
                        });
                        $controlLoad1OnedayCharges = self::getApplyLimit($c1_limits, $control_load_1_oneday_usage, $rate['plan_rate_limit'], 'c1');
                        $controlLoad1SupplyCharges = $getDaysCount * $rate['control_load_1_daily_supply_charges'];



                        // control load one off peak 
                        $c1_limits_offpeak = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'c1_offpeak';
                        });

                        // 
                        $controlLoadOneOffPeak_oneday = $controlLoadOneOffPeak / $getDaysCount;
                        $controlLoadOneOffPeakCharge = self::getApplyLimit($c1_limits_offpeak, $controlLoadOneOffPeak_oneday, $rate['plan_rate_limit'], 'c1_limits_offpeak');
                   
                        $c1_limits_offpeak = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'c1_shoulder';
                        });

                        $controlLoadOneShoulder_oneday = $controlLoadOneShoulder / $getDaysCount;
                        $controlLoadOneShoulderCharge = self::getApplyLimit($c1_limits_offpeak, $controlLoadOneShoulder_oneday, $rate['plan_rate_limit'], 'c1_shoulder');
                    }

                    if ((isset($request['shoulder_control_load']) && $request['shoulder_control_load'] == 'on') || (isset($request['offpeak_control_load']) && $request['offpeak_control_load'] == 'on')) {
                        //calculate c1 only if timeofuse meter type
                        if ($request['elec_metertype'] == 'timeofuse') {
                            $control_load_1_oneday_usage = $controlLoadOneUsage / $getDaysCount;
                            $c1_limits = array_filter($rate['plan_rate_limit'], function ($value) {
                                return $value['limit_type'] == 'c1';
                            });
                            $controlLoad1OnedayCharges = self::getApplyLimit($c1_limits, $control_load_1_oneday_usage, $rate['plan_rate_limit'], 'c1');
                           
                            // control load one off peak 
                            $c1_limits_offpeak = array_filter($rate['plan_rate_limit'], function ($value) {
                                return $value['limit_type'] == 'c1_offpeak';
                            });
                            $controlLoadOneOffPeak_oneday = $controlLoadOneOffPeak / $getDaysCount;
                            $controlLoadOneOffPeakCharge = self::getApplyLimit($c1_limits_offpeak, $controlLoadOneOffPeak_oneday, $rate['plan_rate_limit'], 'c1_limits_offpeak');
                            //$controlLoadOneOffPeakCharges = $controlLoadOneOffPeakCharge * $getDaysCount;
                            // control load one shoulder  
                            $c1_limits_offpeak = array_filter($rate['plan_rate_limit'], function ($value) {
                                return $value['limit_type'] == 'c1_shoulder';
                            });

                            $controlLoadOneShoulder_oneday = $controlLoadOneShoulder / $getDaysCount;
                            $controlLoadOneShoulderCharge = self::getApplyLimit($c1_limits_offpeak, $controlLoadOneShoulder_oneday, $rate['plan_rate_limit'], 'c1_shoulder');
                            
                        }
                        //calculate c2 for both meter type
                        $control_load_2_oneday_usage = $controlLoadTwoUsage / $getDaysCount;
                        $c2_limits = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'c2';
                        });
                        $controlLoad2OnedayCharges = self::getApplyLimit($c2_limits, $control_load_2_oneday_usage, $rate['plan_rate_limit'], 'c2');
                        $controlLoad1SupplyCharges = $getDaysCount * $rate['control_load_1_daily_supply_charges'];
                        $controlLoad2SupplyCharges = $getDaysCount * $rate['control_load_2_daily_supply_charges'];

                        // control load two off peak 
                        $c2_limits_offpeak = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'c2_offpeak';
                        });

                        $controlLoadTwoOffPeak_oneday = $controlLoadTwoOffPeak / $getDaysCount;
                        $controlLoadTwoOffPeakCharge = self::getApplyLimit($c2_limits_offpeak, $controlLoadTwoOffPeak_oneday, $rate['plan_rate_limit'], 'c2_offpeak');
                        
                        // control load two shoulder 
                        $c2_limits_shoulder = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'c2_shoulder';
                        });

                        $controlLoadTwoShoulder_oneday = $controlLoadTwoShoulder / $getDaysCount;
                        $controlLoadTwoShoulderCharge = self::getApplyLimit($c2_limits_shoulder, $controlLoadTwoShoulder_oneday, $rate['plan_rate_limit'], 'c2_shoulder');
                        
                    }

                    if ($request['elec_metertype'] == 'peak') {
                        //get one day total charges
                        $one_day_usage_charges = $peak_charges / 100;
                    } elseif ($request['elec_metertype'] == 'double') {
                        //get one day total charges



                        $one_day_usage_charges = ($peak_charges + $controlLoad1OnedayCharges + $controlLoad2OnedayCharges + $controlLoadOneOffPeakCharge + $controlLoadOneShoulderCharge + $controlLoadTwoOffPeakCharge + $controlLoadTwoShoulderCharge) / 100;
                    } elseif ($request['elec_metertype'] == 'timeofuse') {
                        //get offpeak charges
                        $offpeak_limits = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'offpeak';
                        });
                        $off_peak_charges = self::getApplyLimit($offpeak_limits, $daily_off_peak_usage); //get shoulder charges
                        $shoulder_limits = array_filter($rate['plan_rate_limit'], function ($value) {
                            return $value['limit_type'] == 'shoulder';
                        });
                        $shoulder_charges = self::getApplyLimit($shoulder_limits, $daily_shoulder_usage); //get one day total charges
                        $one_day_usage_charges = ($peak_charges + $off_peak_charges + $shoulder_charges + $controlLoad1OnedayCharges + $controlLoad2OnedayCharges + $controlLoadOneOffPeakCharge + $controlLoadOneShoulderCharge + $controlLoadTwoOffPeakCharge + $controlLoadTwoShoulderCharge) / 100;
                    }



                    $total_usage_charges = $one_day_usage_charges * $getDaysCount;
                    $dailySupplyCharges = (($rate['daily_supply_charges'] * $getDaysCount) + $controlLoad1SupplyCharges + $controlLoad2SupplyCharges) / 100;
                    //biil before discount
                    $bill_before_discount = $total_usage_charges + $dailySupplyCharges;
                    //discount on usage
                    $discount = 0;
                    $gurrentedDiscount = 0;
                    $discountWithDual = 0;
                    if ($rate['pay_day_discount_usage']) {
                        $matchedPlans[$k]['applied_pay_day_discount_usage'] = 'yes';
                        $discount = $discount + (($total_usage_charges * $rate['pay_day_discount_usage']) / 100);
                    }
                    if ($rate['gurrented_discount_usage']) {
                        $matchedPlans[$k]['applied_gurrented_discount_usage'] = 'yes';
                        $gurrentedDiscount += ($total_usage_charges * $rate['gurrented_discount_usage']) / 100;
                        $discount = $discount + (($total_usage_charges * $rate['gurrented_discount_usage']) / 100);
                    }
                    if ($rate['direct_debit_discount_usage']) {
                        $matchedPlans[$k]['applied_direct_debit_discount_usage'] = 'yes';
                        $discount = $discount + (($total_usage_charges * $rate['direct_debit_discount_usage']) / 100);
                    }
                    if ($rate['dual_fuel_discount_usage']) {
                        $matchedPlans[$k]['applied_dual_fuel_discount_usage'] = 'yes';
                        $discountWithDual = $discountWithDual + (($total_usage_charges * $rate['dual_fuel_discount_usage']) / 100);
                    }
                    //get supply charges
                    if ($rate['pay_day_discount_supply']) {
                        $matchedPlans[$k]['applied_pay_day_discount_supply'] = 'yes';
                        $discount = $discount + (($dailySupplyCharges * $rate['pay_day_discount_supply']) / 100);
                    }
                    if ($rate['gurrented_discount_supply']) {
                        $matchedPlans[$k]['applied_gurrented_discount_supply'] = 'yes';
                        $gurrentedDiscount += ($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100;
                        $discount = $discount + (($dailySupplyCharges * $rate['gurrented_discount_supply']) / 100);
                    }
                    if ($rate['direct_debit_discount_supply']) {
                        $matchedPlans[$k]['applied_direct_debit_discount_supply'] = 'yes';
                        $discount = $discount + (($dailySupplyCharges * $rate['direct_debit_discount_supply']) / 100);
                    }
                    if ($rate['dual_fuel_discount_supply']) {
                        $matchedPlans[$k]['applied_dual_fuel_discount_supply'] = 'yes';
                        $discountWithDual = $discountWithDual + (($dailySupplyCharges * $rate['dual_fuel_discount_supply']) / 100);
                    }
                    $discountWithDual += $discount;
                    //bill before discount
                    $bill_before_discount = $bill_before_discount - $gurrentedDiscount;
                    $expectedBillAmount = ($bill_before_discount * $rate['gst_rate'] / 100) + $bill_before_discount;

                    $matchedPlans[$k]['expected_bill_amount'] = ceil(round($expectedBillAmount, 2));
                    //get total charges
                    $billAfterDiscount = ($total_usage_charges + $dailySupplyCharges) - $discount;
                    $billAfterDiscountWithDual = ($total_usage_charges + $dailySupplyCharges) - $discountWithDual;


                    //Recurring meter charges and credit bonus
                    $bill_before_discount = $bill_before_discount + $recurringMeterCharges - $creditBonus;
                    //end here


                    $expectedBillAmount = ($bill_before_discount * $rate['gst_rate'] / 100) + $bill_before_discount;


                    //if (isset($request['is_groupon'])) {
                    $expectedBillAmount -= config('app.groupon_electricity_subtracted_amount');
                    //}
                    $monthlyExpectedBillAmount = ($expectedBillAmount / $getDaysCount) * 31;
                    $quaterlyExpectedBillAmount = ($expectedBillAmount / $getDaysCount) * 91;
                    $annuallyExpectedBillAmount = ($expectedBillAmount / $getDaysCount) * 365;

                    //Recurring meter charges and credit bonus
                    $billAfterDiscount = $billAfterDiscount + $recurringMeterCharges - $creditBonus;
                    $billAfterDiscountWithDual = $billAfterDiscountWithDual + $recurringMeterCharges - $creditBonus;
                    //end here

                    //get gst charges
                    $discountedBillAmount = ($billAfterDiscount * $rate['gst_rate'] / 100) + $billAfterDiscount;
                    $discountedBillWithDual = ($billAfterDiscountWithDual * $rate['gst_rate'] / 100) + $billAfterDiscountWithDual;

                    //get solor Buy Back Price
                    $solor_rebate = 0;
                    if ($request['solar_panel'] == 1) {
                        $solor_rebate = self::getSolorByBackPrice($request['solar_usage'], $plan['plan_solar_rate'], $plan['is_both_solar_plan'], $request['solor_tariff']);
                        $discountedBillAmount = $discountedBillAmount - $solor_rebate;
                        $discountedBillWithDual = $discountedBillWithDual - $solor_rebate;
                        $expectedBillAmount = $expectedBillAmount - $solor_rebate;
                       
                    }

                    //if (isset($request['is_groupon'])) {
                    $discountedBillAmount -= config('app.groupon_electricity_subtracted_amount');
                    $discountedBillWithDual -= config('app.groupon_electricity_subtracted_amount');
                    //}
                    $monthlyDiscountedBillAmount = ($discountedBillAmount / $getDaysCount) * 31;
                    $quaterlyDiscountedBillAmount = ($discountedBillAmount / $getDaysCount) * 91;
                    $annuallyDiscountedBillAmount = ($discountedBillAmount / $getDaysCount) * 365;

                    $monthlyDiscountedBillWithDual = ($discountedBillWithDual / $getDaysCount) * 31;
                    $quaterlyDiscountedBillWithDual = ($discountedBillWithDual / $getDaysCount) * 91;
                    $annuallyDiscountedBillWithDual = ($discountedBillWithDual / $getDaysCount) * 365;

                    $matchedPlans[$k]['expected_bill_amount'] = ceil(round($expectedBillAmount, 2));
                    


                    $matchedPlans[$k]['expected_monthly_bill_amount'] = ceil(round($monthlyExpectedBillAmount, 2));
                   

                    $matchedPlans[$k]['expected_quaterly_bill_amount'] = ceil(round($quaterlyExpectedBillAmount, 2));
                    
                    $matchedPlans[$k]['expected_annually_bill_amount'] = ceil(round($annuallyExpectedBillAmount, 2));
                    
                    //discounted amounts
                    $matchedPlans[$k]['expected_discounted_bill_amount'] = ceil(round($discountedBillAmount, 2));
                   
                    $matchedPlans[$k]['expected_monthly_discounted_bill_amount'] = ceil(round($monthlyDiscountedBillAmount, 2));
                   
                    $matchedPlans[$k]['expected_quaterly_discounted_bill_amount'] = ceil(round($quaterlyDiscountedBillAmount, 2));
                    
                    $matchedPlans[$k]['expected_annually_discounted_bill_amount'] = ceil(round($annuallyDiscountedBillAmount, 2));
                    //saving ammount value start here

                    //dd($matchedPlans[$k]['expected_discounted_bill_amount']);
                    if (isset($electricityBillAmount) && !empty($electricityBillAmount)) {
                        $electricityBillAmount_calc = $electricityBillAmount - $matchedPlans[$k]['expected_discounted_bill_amount'];
                        if ($electricityBillAmount_calc <= 0) {
                            $matchedPlans[$k]['savings_bill_amount'] = "No savings";
                            $matchedPlans[$k]['savings_monthly_bill_amount'] = 'No savings';
                            $matchedPlans[$k]['savings_annually_bill_amount'] = 'No savings';
                            $matchedPlans[$k]['savings_quaterly_bill_amount'] = 'No savings';
                        } else {
                            $savings_bill_amount = $electricityBillAmount_calc;

                            $savings_monthly_bill_amount = ($electricityBillAmount_calc / $getDaysCount) * 31;
                            $savings_quaterly_bill_amount = ($electricityBillAmount_calc / $getDaysCount) * 91;
                            $savings_annually_bill_amount = ($electricityBillAmount_calc / $getDaysCount) * 365;

                            $matchedPlans[$k]['savings_bill_amount'] = round($savings_bill_amount, 2);
                            $matchedPlans[$k]['savings_monthly_bill_amount'] = round($savings_monthly_bill_amount, 2);
                            $matchedPlans[$k]['savings_quaterly_bill_amount'] = round($savings_quaterly_bill_amount, 2);
                            $matchedPlans[$k]['savings_annually_bill_amount'] = round($savings_annually_bill_amount, 2);
                        }
                    } else {
                        $matchedPlans[$k]['savings_bill_amount'] = '';
                        $matchedPlans[$k]['savings_monthly_bill_amount'] = '';
                        $matchedPlans[$k]['savings_annually_bill_amount'] = '';
                        $matchedPlans[$k]['savings_quaterly_bill_amount'] = '';
                    }
                   
                    //discounted amount with dual plans
                    $matchedPlans[$k]['expected_discounted_amount_with_duel'] = ceil(round($discountedBillWithDual, 2));
                   

                    $matchedPlans[$k]['expected_monthly_discounted_amount_with_duel'] = ceil(round($monthlyDiscountedBillWithDual, 2));
                   

                    $matchedPlans[$k]['expected_quaterly_discounted_amount_with_duel'] = ceil(round($quaterlyDiscountedBillWithDual, 2));
                   


                    $matchedPlans[$k]['expected_annually_discounted_amount_with_duel'] = ceil(round($annuallyDiscountedBillWithDual, 2));

                    $matchedPlans[$k]['expected_annual_adjustments'] = $expectedAnnualAdjustments;

                    
                    $matchedPlans[$k]['terms_condition'] = $plan['terms_condition'];
                   // $matchedPlans[$k]['plan_document'] =  $fullUrlPlanDocument;
                    $matchedPlans[$k]['pay_day_discount_usage'] = $rate['pay_day_discount_usage'];
                    $matchedPlans[$k]['gurrented_discount_usage'] = $rate['gurrented_discount_usage'];
                    $matchedPlans[$k]['rates'] = $plan['rate'];
                   
                    $matchedPlans[$k]['solar_rates'] = $plan['plan_solar_rate'];
                    if (isset($plan['plan_solar_rate_normal']['solar_price'])) {
                        $matchedPlans[$k]['plan_solar_rate_normal'] = $plan['plan_solar_rate_normal']['solar_price'];
                    } else {
                        $matchedPlans[$k]['solar_rate_normal'] = 0;
                    }
                    if (isset($plan['plan_solar_rate_permimum']['solar_price'])) {
                        $matchedPlans[$k]['plan_solar_rate_permimum'] = $plan['plan_solar_rate_permimum']['solar_price'];
                    } else {
                        $matchedPlans[$k]['plan_solar_rate_permimum'] = 0;
                    }
                    $matchedPlans[$k]['exit_fee_option'] = $rate['exit_fee_option'];
                    $matchedPlans[$k]['exit_fee'] = $rate['exit_fee'];
                    $matchedPlans[$k]['show_desc'] = 'Approx. charges (incl GST) for ' . $getDaysCount . ' days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' kWh daily usage.';
                    $matchedPlans[$k]['show_monthly_desc'] = 'Approx. charges (incl GST) for 31 days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' kWh daily usage.';
                    $matchedPlans[$k]['show_quaterly_desc'] = 'Approx. charges (incl GST) for 91 days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' kWh daily usage.';
                    $matchedPlans[$k]['show_annually_desc'] = 'Approx. charges (incl GST) for 365 days based on average ' . sprintf("%.2f", $totalOneDayUsage) . ' kWh daily usage.';

                    $matchedPlans[$k]['plan_tags'] = $plan['get_plan_tags'];
                    $matchedPlans[$k]['tariff_type'] = $rate['type'];
                    $matchedPlans[$k]['dual_only'] = $plan['dual_only'];
                    
                   
                    if ($rate['demand_usage_check'] == 1 && $plan['demand_usage_check'] == 1 &&  (isset($request['demand'])) && $request['demand'] == true) {
                        
                        $totalDemand = self::calculateDemandUsage($plan, $request, $meterType, $rate, $getDaysCount);
                        
                        $matchedPlans[$k]['demand']['calculation'] = $totalDemand['total_demand'];
                        $matchedPlans[$k]['demand']['total_days'] = $totalDemand['total_days'];
                        $matchedPlans[$k]['demand']['demand1_charges'] = $totalDemand['demand1_charges'];
                        $matchedPlans[$k]['demand']['demand2_charges'] = $totalDemand['demand2_charges'];
                        $matchedPlans[$k]['demand']['demand3_charges'] = $totalDemand['demand3_charges'];
                        $matchedPlans[$k]['demand']['demand4_charges'] = $totalDemand['demand4_charges'];
                    } else {

                        $matchedPlans[$k]['demand']['calculation'] = "Demand cost not available";
                        $matchedPlans[$k]['demand']['total_days'] = "no days";
                    }
                    if (isset($request['include_all_rates']) && $request['include_all_rates'] == 0) {
                        unset($matchedPlans[$k]['rates']);
                        $matchedPlans[$k]['rates'][0] = $rate;
                    } elseif (!isset($request['include_all_rates'])) {
                        unset($matchedPlans[$k]['rates']);
                        $matchedPlans[$k]['rates'][0] = $rate;
                    }
                    $matchedPlans[$k]['demand']['plan_rate_id'] = $rate['id'];
                }
            }
        }
        
        return $matchedPlans;
    }


    static function getPlanBasicInfo($plan)
    {
      
        $matchedPlans['id'] = $plan['id'];
        $matchedPlans['provider_id'] = $plan['provider_id'];
        $matchedPlans['plan_name'] = $plan['plan_name'];
        $matchedPlans['energy_type'] = $plan['energy_type'];
        $matchedPlans['contract_length'] = $plan['contract_length'];
        $matchedPlans['features'] = $plan['plan_features'];
        $matchedPlans['green_options'] = $plan['green_options'];
        $matchedPlans['green_options_desc'] = $plan['green_options_desc'];
        $matchedPlans['solar_compatible'] = $plan['solar_compatible'];
       // $matchedPlans['solar_desc'] = $plan['solar_desc'];
       // $matchedPlans['solar_price'] = $plan['solor_price'];
        $matchedPlans['benefit_term'] = $plan['benefit_term'];
        $matchedPlans['credit_card_service_fee'] = $plan['credit_card_service_fee'];
        $matchedPlans['counter_fee'] = $plan['counter_fee'];
        $matchedPlans['paper_bill_fee'] = $plan['paper_bill_fee'];
        $matchedPlans['cooling_off_period'] = $plan['cooling_off_period'];
        $matchedPlans['other_fee_section'] = $plan['other_fee_section'];
        $matchedPlans['plan_bonus'] = $plan['plan_bonus'];
        $matchedPlans['plan_bonus_desc'] = $plan['plan_bonus_desc'];
        $matchedPlans['billing_options'] = $plan['billing_options'];
        $matchedPlans['payment_options'] = $plan['payment_options'];
        $matchedPlans['plan_desc'] = $plan['plan_desc'];
        $matchedPlans['plan_features'] = $plan['plan_features'];
        $matchedPlans['view_discount'] = $plan['view_discount'];
        $matchedPlans['view_benefit'] = $plan['view_benefit'];
        $matchedPlans['view_bonus'] = $plan['view_bonus'];
        $matchedPlans['view_contract'] = $plan['view_contract'];
        $matchedPlans['view_exit_fee'] = $plan['view_exit_fee'];
       // $matchedPlans['why_us'] = $plan['provider']['content']['why_us'];
        $matchedPlans['is_bundle_dual_plan'] = $plan['is_bundle_dual_plan'];
        $matchedPlans['bundle_code'] = $plan['bundle_code'];
        $matchedPlans['show_solar_plan'] = $plan['show_solar_plan'];

        return $matchedPlans;
    }




    static function calculateDemandUsage($plan, $request, $meter_type,$rate,$get_days_count){
        $day_Available_kva = '';
        $one_day_demand_rate1_peak_usage = 0;
        $one_day_demand_rate1_off_peak_usage = 0;
        $one_day_demand_rate1_shoulder_usage = 0;
        //rate 2 
        $one_day_demand_rate2_peak_usage = 0;
        $one_day_demand_rate2_off_peak_usage = 0;
        $one_day_demand_rate2_shoulder_usage = 0;
        //rate 3
        $one_day_demand_rate3_peak_usage = 0;
        $one_day_demand_rate3_off_peak_usage = 0;
        $one_day_demand_rate3_shoulder_usage = 0;
        //rate 4
        $one_day_demand_rate4_peak_usage = 0;
        $one_day_demand_rate4_off_peak_usage = 0;
        $one_day_demand_rate4_shoulder_usage = 0;

        $demand_rate2_peak_usage = 0;
        $demand_rate2_off_peak_usage = 0;
        $demand_rate2_shoulder_usage = 0;
        $demand_rate2_days = 0;

        $demand_rate3_peak_usage = 0;
        $demand_rate3_off_peak_usage = 0;
        $demand_rate3_shoulder_usage = 0;
        $demand_rate3_days = 0;

        $demand_rate4_peak_usage = 0;
        $demand_rate4_off_peak_usage = 0;
        $demand_rate4_shoulder_usage = 0;
        $demand_rate4_days = 0;
        $demand_usage_type           = $request['demand_data']['demand_usage_type'];
        $demand_rate1_peak_usage     = $request['demand_data']['demand_rate1_peak_usage'];
        $demand_rate1_off_peak_usage = $request['demand_data']['demand_rate1_off_peak_usage'];
        $demand_rate1_shoulder_usage = $request['demand_data']['demand_rate1_shoulder_usage'];
       
        if ($demand_usage_type == 2)
            $demand_rate1_days           = !empty($request['demand_data']['demand_rate1_days']) ? $request['demand_data']['demand_rate1_days'] : $get_days_count;
        else
            $demand_rate1_days           = !empty($request['demand_data']['demand_rate1_days']) ? $request['demand_data']['demand_rate1_days'] : 0;

        $days = $request['demand_data']['demand_rate1_days'];



        // if demand 2 3 4 availbe else 0
        $demand_rate2_peak_usage     = isset($request['demand_data']['demand_rate2_peak_usage']) ? $request['demand_data']['demand_rate2_peak_usage'] : 0;

        $demand_rate2_off_peak_usage     = isset($request['demand_data']['demand_rate2_off_peak_usage']) ? $request['demand_data']['demand_rate2_off_peak_usage'] : 0;

        $demand_rate2_shoulder_usage     = isset($request['demand_data']['demand_rate2_shoulder_usage']) ? $request['demand_data']['demand_rate2_shoulder_usage'] : 0;

        $demand_rate2_days     = isset($request['demand_data']['demand_rate2_days']) ? $request['demand_data']['demand_rate2_days'] : 0;



        //rate 3
        $demand_rate3_peak_usage     = isset($request['demand_data']['demand_rate3_peak_usage']) ? $request['demand_data']['demand_rate3_peak_usage'] : 0;

        $demand_rate3_off_peak_usage     = isset($request['demand_data']['demand_rate3_off_peak_usage']) ? $request['demand_data']['demand_rate3_off_peak_usage'] : 0;

        $demand_rate3_shoulder_usage     = isset($request['demand_data']['demand_rate3_shoulder_usage']) ? $request['demand_data']['demand_rate3_shoulder_usage'] : 0;

        $demand_rate3_days     = isset($request['demand_data']['demand_rate3_days']) ? $request['demand_data']['demand_rate3_days'] : 0;

        //rate 4
        $demand_rate4_peak_usage     = isset($request['demand_data']['demand_rate4_peak_usage']) ? $request['demand_data']['demand_rate4_peak_usage'] : 0;

        $demand_rate4_off_peak_usage     = isset($request['demand_data']['demand_rate4_off_peak_usage']) ? $request['demand_data']['demand_rate4_off_peak_usage'] : 0;

        $demand_rate4_shoulder_usage     = isset($request['demand_data']['demand_rate4_shoulder_usage']) ? $request['demand_data']['demand_rate4_shoulder_usage'] : 0;

        $demand_rate4_days     = isset($request['demand_data']['demand_rate4_days']) ? $request['demand_data']['demand_rate4_days'] : 0;

        if ($demand_usage_type == 1) {

            if ($demand_rate4_days == 0 && $demand_rate3_days == 0 && $demand_rate2_days == 0 && $demand_rate1_days == 0) {

                $demand_rate4_days = 1;
                $demand_rate3_days = 1;
                $demand_rate2_days = 1;
                $demand_rate1_days = 1;
                $day_Available_kva = "no";
            }
        }

        $one_day_demand_rate1_peak_usage = $demand_rate1_peak_usage;
        $one_day_demand_rate1_off_peak_usage = $demand_rate1_off_peak_usage;
        $one_day_demand_rate1_shoulder_usage = $demand_rate1_shoulder_usage;
        //rate 2 
        $one_day_demand_rate2_peak_usage = $demand_rate2_peak_usage;
        $one_day_demand_rate2_off_peak_usage = $demand_rate2_off_peak_usage;
        $one_day_demand_rate2_shoulder_usage = $demand_rate2_shoulder_usage;
        //rate 3
        $one_day_demand_rate3_peak_usage = $demand_rate3_peak_usage;
        $one_day_demand_rate3_off_peak_usage = $demand_rate3_off_peak_usage;
        $one_day_demand_rate3_shoulder_usage = $demand_rate3_shoulder_usage;
        //rate 4
        $one_day_demand_rate4_peak_usage = $demand_rate4_peak_usage;
        $one_day_demand_rate4_off_peak_usage = $demand_rate4_off_peak_usage;
        $one_day_demand_rate4_shoulder_usage = $demand_rate4_shoulder_usage;



        $input_tariiff_code = $request['demand_data']['demand_tariff_code'];

        $tariff = array_filter($rate['tariff_info'], function ($tariff) use ($input_tariiff_code) {

            if ($tariff['tariff_code_ref_id'] == $input_tariiff_code) {
                return true;
            } else {
                $alias_arr = explode(',', $tariff['tariff_code_aliases']);
                if (in_array($input_tariiff_code, $alias_arr)) {
                    return true;
                }
            }
        });

        $tariff = array_values($tariff);
        $demand1_peak = [];
        $demand1_off_peak = [];
        $demand1_shoulder = [];

        //demand 1 rate calculation
        if (count($tariff)) {
            $tariff = $tariff[0];
            $demand1_peak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '1' && $value['usage_type'] == 'peak')
                    return true;
            });
          
            $demand1_peak_charges = self::getApplyLimit($demand1_peak, $one_day_demand_rate1_peak_usage);


            $demand1_offpeak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '1' && $value['usage_type'] == 'off_peak')
                    return true;
            });
           
            $demand1_off_peak_charges = self::getApplyLimit($demand1_offpeak, $one_day_demand_rate1_off_peak_usage);


            $demand1_shoulder = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '1' && $value['usage_type'] == 'shoulder')
                    return true;
            });

            $demand1_shoulder_charges = self::getApplyLimit($demand1_shoulder, $one_day_demand_rate1_shoulder_usage);


            $demand2_peak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '2' && $value['usage_type'] == 'peak')
                    return true;
            });

            if (count($demand2_peak))
                $demand2_peak_charges = self::getApplyLimit($demand2_peak, $one_day_demand_rate2_peak_usage);
            else
                $demand2_peak_charges = self::getApplyLimit($demand1_peak, $one_day_demand_rate2_peak_usage);


            $demand2_offpeak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '2' && $value['usage_type'] == 'off_peak')
                    return true;
            });
            if (count($demand2_offpeak))
                $demand2_off_peak_charges = self::getApplyLimit($demand2_offpeak, $one_day_demand_rate2_off_peak_usage);
            else
                $demand2_off_peak_charges = self::getApplyLimit($demand1_offpeak, $one_day_demand_rate2_off_peak_usage);


            $demand2_shoulder = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '2' && $value['usage_type'] == 'shoulder')
                    return true;
            });

            if (count($demand2_shoulder))
                $demand2_shoulder_charges = self::getApplyLimit($demand2_shoulder, $one_day_demand_rate2_shoulder_usage);
            else
                $demand2_shoulder_charges = self::getApplyLimit($demand1_shoulder, $one_day_demand_rate2_shoulder_usage);

            //calculation of demand rate 3 

            $demand3_peak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '3' && $value['usage_type'] == 'peak')
                    return true;
            });


            if (count($demand3_peak))
                $demand3_peak_charges = self::getApplyLimit($demand3_peak, $one_day_demand_rate3_peak_usage);
            else
                $demand3_peak_charges = self::getApplyLimit($demand1_peak, $one_day_demand_rate3_peak_usage);


            $demand3_offpeak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '3' && $value['usage_type'] == 'off_peak')
                    return true;
            });

            if (count($demand3_offpeak))
                $demand3_off_peak_charges = self::getApplyLimit($demand3_offpeak, $one_day_demand_rate3_off_peak_usage);
            else
                $demand3_off_peak_charges = self::getApplyLimit($demand1_offpeak, $one_day_demand_rate3_off_peak_usage);


            $demand3_shoulder = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '3' && $value['usage_type'] == 'shoulder')
                    return true;
            });


            if (count($demand3_shoulder))
                $demand3_shoulder_charges = self::getApplyLimit($demand3_shoulder, $one_day_demand_rate3_shoulder_usage);
            else
                $demand3_shoulder_charges = self::getApplyLimit($demand1_shoulder, $one_day_demand_rate3_shoulder_usage);

            //calculate demand rate 4 

            $demand4_peak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '4' && $value['usage_type'] == 'peak')
                    return true;
            });

            if (count($demand4_peak))
                $demand4_peak_charges = self::getApplyLimit($demand4_peak, $one_day_demand_rate4_peak_usage);
            else
                $demand4_peak_charges = self::getApplyLimit($demand1_peak, $one_day_demand_rate4_peak_usage);


            $demand4_offpeak = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == '4' && $value['usage_type'] == 'off_peak')
                    return true;
            });

            if (count($demand4_offpeak))
                $demand4_off_peak_charges = self::getApplyLimit($demand4_offpeak, $one_day_demand_rate4_off_peak_usage);
            else
                $demand4_off_peak_charges = self::getApplyLimit($demand1_offpeak, $one_day_demand_rate4_off_peak_usage);


            $demand4_shoulder = array_filter($tariff['tariff_rates'], function ($value) {
                if ($value['season_rate_type'] == 'rate_4' && $value['usage_type'] == 'shoulder')
                    return true;
            });

            if (count($demand4_shoulder))
                $demand4_shoulder_charges = self::getApplyLimit($demand4_shoulder, $one_day_demand_rate4_shoulder_usage);
            else
                $demand4_shoulder_charges = self::getApplyLimit($demand1_shoulder, $one_day_demand_rate4_shoulder_usage);

            $demand1_peak_charges = $demand1_peak_charges / 100 * $demand_rate1_days;
            $demand2_peak_charges = $demand2_peak_charges / 100 * $demand_rate2_days;
            $demand3_peak_charges = $demand3_peak_charges / 100 * $demand_rate3_days;
            $demand4_peak_charges = $demand4_peak_charges / 100 * $demand_rate4_days;

            $demand1_off_peak_charges = $demand1_off_peak_charges / 100 * $demand_rate1_days;
            $demand2_off_peak_charges = $demand2_off_peak_charges / 100 * $demand_rate2_days;
            $demand3_off_peak_charges = $demand3_off_peak_charges / 100 * $demand_rate3_days;
            $demand4_off_peak_charges = $demand4_off_peak_charges / 100 * $demand_rate4_days;

            $demand1_shoulder_charges = $demand1_shoulder_charges / 100 * $demand_rate1_days;
            $demand2_shoulder_charges = $demand2_shoulder_charges / 100 * $demand_rate2_days;
            $demand3_shoulder_charges = $demand3_shoulder_charges / 100 * $demand_rate3_days;
            $demand4_shoulder_charges = $demand4_shoulder_charges / 100 * $demand_rate4_days;

            $demand['demand1_charges'] =  $demand1_peak_charges + $demand1_off_peak_charges + $demand1_shoulder_charges;
            $demand['demand2_charges'] =  $demand2_peak_charges + $demand1_off_peak_charges + $demand2_shoulder_charges;
            $demand['demand3_charges'] =  $demand3_peak_charges + $demand1_off_peak_charges + $demand3_shoulder_charges;
            $demand['demand4_charges'] =  $demand4_peak_charges + $demand1_off_peak_charges + $demand4_shoulder_charges;

            $total_demand = $demand1_peak_charges + $demand2_peak_charges + $demand3_peak_charges + $demand4_peak_charges + $demand1_off_peak_charges + $demand2_off_peak_charges + $demand3_off_peak_charges + $demand4_off_peak_charges + $demand1_shoulder_charges + $demand2_shoulder_charges + $demand3_shoulder_charges + $demand4_shoulder_charges;


            $supply_discount = 0;

            if ($day_Available_kva == "no") {
                $demand['total_days'] = "";
            } else {
                $demand['total_days'] = $demand_rate1_days + $demand_rate2_days + $demand_rate3_days + $demand_rate4_days;

                if ($demand['total_days'] > $get_days_count) {

                    $demand['total_days'] = $get_days_count;
                }
            }
            $supply_charges = $tariff['tariff_daily_supply'] * $get_days_count / 100;
            $discount =  ($tariff['tariff_discount'] / 100) * $total_demand;
            $total_demand =  $total_demand - $discount;
            //discount 
            $supply_discount = ($tariff['tariff_supply_discount'] / 100) * $supply_charges;
            $supply_charges  = $supply_charges - $supply_discount;

            $demand['total_demand'] = $total_demand + $supply_charges;

            //gst 
            $gst = $rate['gst_rate'] / 100 * $demand['total_demand'];

            $demand['total_demand'] = $demand['total_demand'] + $gst;

            $demand['total_demand'] = ceil(round($demand['total_demand'], 2));
        } else {
            $demand['total_demand'] = "Demand cost not available";
            $demand['total_days']  = "no days";
            $demand['demand1_charges'] =  0;
            $demand['demand2_charges'] =  0;
            $demand['demand3_charges'] =  0;
            $demand['demand4_charges'] =  0;
        }
        return $demand;
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


    /* Name: sortplanbydiscountPlans()
    * Purpose: sort all plans.
    * param: none
    * Date: 12-Oct-2016
    * created By: Debut Infotech
    */
   static function sortPlans($allPlans, $energyType)
   {    
       if ($energyType == 'electricity') {
           if (!empty($allPlans['electricity'])) {
               usort($allPlans['electricity'], function ($a, $b) {
                   if ($a['expected_discounted_bill_amount'] - $b['expected_discounted_bill_amount'] > 0) {
                       return true;
                   } elseif ($a['expected_discounted_bill_amount'] - $b['expected_discounted_bill_amount'] == 0) {
                       return $a['expected_bill_amount'] - $b['expected_bill_amount'];
                   } else {
                       return false;
                   }
               });
           }
       } elseif ($energyType == 'gas') {
           if (!empty($allPlans['gas'])) {
               usort($allPlans['gas'], function ($a, $b) {
                   if ($a['expected_discounted_gas_bill_amount'] - $b['expected_discounted_gas_bill_amount'] > 0) {
                       return true;
                   } elseif ($a['expected_discounted_gas_bill_amount'] - $b['expected_discounted_gas_bill_amount'] == 0) {
                       return $a['expected_gas_bill_amount'] - $b['expected_gas_bill_amount'];
                   } else {
                       return false;
                   }
               });
           }
       } elseif ($energyType == 'electricitygas') {
           if (!empty($allPlans['electricity'])) {
               usort($allPlans['electricity'], function ($a, $b) {
                   if ($a['expected_discounted_bill_amount'] - $b['expected_discounted_bill_amount'] > 0) {
                       return true;
                   } elseif ($a['expected_discounted_bill_amount'] - $b['expected_discounted_bill_amount'] == 0) {
                       return $a['expected_bill_amount'] - $b['expected_bill_amount'];
                   } else {
                       return false;
                   }
               });
           }
           if (!empty($allPlans['gas'])) {
               usort($allPlans['gas'], function ($a, $b) {
                   if ($a['expected_discounted_gas_bill_amount'] - $b['expected_discounted_gas_bill_amount'] > 0) {
                       return true;
                   } elseif ($a['expected_discounted_gas_bill_amount'] - $b['expected_discounted_gas_bill_amount'] == 0) {
                       return $a['expected_gas_bill_amount'] - $b['expected_gas_bill_amount'];
                   } else {
                       return false;
                   }
               });
           }
           if (!empty($allPlans['combined_plans'])) {
               usort($allPlans['combined_plans'], function ($a, $b) {
                   if ($a['electricity']['expected_discounted_bill_amount'] - $b['electricity']['expected_discounted_bill_amount'] > 0) {
                       return true;
                   } elseif ($a['electricity']['expected_discounted_bill_amount'] - $b['electricity']['expected_discounted_bill_amount'] == 0) {
                       return $a['electricity']['expected_bill_amount'] - $b['electricity']['expected_bill_amount'];
                   } else {
                       return false;
                   }
               });
           }
           if (isset($allPlans['combo_plans']) && !empty($allPlans['combo_plans'])) {

               usort($allPlans['combo_plans'], function ($a, $b) {
                   if ($a['electricity']['expected_discounted_bill_amount'] - $b['electricity']['expected_discounted_bill_amount'] > 0) {
                       return true;
                   } elseif ($a['electricity']['expected_discounted_bill_amount'] - $b['electricity']['expected_discounted_bill_amount'] == 0) {
                       if (($a['gas']['expected_discounted_gas_bill_amount'] - $b['gas']['expected_discounted_gas_bill_amount'] > 0))
                           return true;
                       else
                           return false;
                   } else {
                       return false;
                   }
               });
           }
       }
       return $allPlans;
   }


   static function dualsortPlans($allPlans, $energyType)
   {
       if ($energyType == 'electricitygas') {

           if (!empty($allPlans['combined_plans'])) {
               usort($allPlans['combined_plans'], function ($a, $b) {
                   if (($a['electricity']['expected_discounted_bill_amount'] + $a['gas']['expected_discounted_gas_bill_amount']) - ($b['electricity']['expected_discounted_bill_amount'] + $b['gas']['expected_discounted_gas_bill_amount']) > 0) {
                       return true;
                   } elseif (($a['electricity']['expected_discounted_bill_amount'] + $a['gas']['expected_discounted_gas_bill_amount']) - ($b['electricity']['expected_discounted_bill_amount'] + $b['gas']['expected_discounted_gas_bill_amount']) == 0) {
                       //return true;
                       return false;
                   } else {
                       return false;
                   }
               });
           }
           if (isset($allPlans['combo_plans']) && !empty($allPlans['combo_plans'])) {

               usort($allPlans['combo_plans'], function ($a, $b) {

                   if (($a['electricity']['expected_discounted_bill_amount'] + $a['gas']['expected_discounted_gas_bill_amount']) - ($b['electricity']['expected_discounted_bill_amount'] + $b['gas']['expected_discounted_gas_bill_amount']) > 0) {
                       return true;
                   } elseif (($a['electricity']['expected_discounted_bill_amount'] + $a['gas']['expected_discounted_gas_bill_amount']) - ($b['electricity']['expected_discounted_bill_amount'] + $b['gas']['expected_discounted_gas_bill_amount']) == 0) {
                       //return true;
                       return false;
                   } else {
                       return false;
                   }
               });
           }
           
       }
       return $allPlans;
   }

   static function comboPlans($sortedPlans)
   {
      
       $elecPlans = $sortedPlans['electricity'];
       $gasPlans = $sortedPlans['gas'];
       
       $Plans['comboPlan'] = [];
       $Plans['combinePlan'] = [];
       $Plans['comboPlan_id'] = [];
       $Plans['combinePlan_id']=[];
       $combinePlans = array();
       if (!empty($elecPlans) && !empty($gasPlans)) {
           foreach ($elecPlans as $k => $ePlan) {
               $elecProviderId = $ePlan['provider_id'];
               $elecDualOnly = $ePlan['dual_only'];

               $elecBundleDualPlan = $ePlan['is_bundle_dual_plan'];
              
               $elecBundleCode = $ePlan['bundle_code'];
               
               foreach ($gasPlans as $j => $gPlan) {
               
                       if (($gPlan['is_bundle_dual_plan'] == 0 && $elecBundleDualPlan == 0) || (($gPlan['is_bundle_dual_plan'] == 1 && $elecBundleDualPlan == 1) && ($gPlan['bundle_code'] == $elecBundleCode))) {
                       
                           if ($gPlan['provider_id'] == $elecProviderId) {
                          
                               $saveElecExpectedBillAmount = $ePlan['expected_discounted_bill_amount'];
                               $saveGasExpectedBillAmount = $gPlan['expected_discounted_gas_bill_amount'];
                               $ePlan['expected_discounted_bill_amount'] = $ePlan['expected_discounted_amount_with_duel'];
                               $gPlan['expected_discounted_gas_bill_amount'] = $gPlan['expected_discounted_amount_with_duel'];
                               $Plans['combinePlan_id'][$k]['electricity_id'] = $ePlan['id'];
                               $Plans['combinePlan'][$k]['electricity'] = $ePlan;
                               $Plans['combinePlan'][$k]['gas'] = $gPlan;
                               $Plans['combinePlan_id'][$k]['gas_id'] = $gPlan['id'];
                               $ePlan['expected_discounted_bill_amount'] = $saveElecExpectedBillAmount;
                               $gPlan['expected_discounted_gas_bill_amount'] = $saveGasExpectedBillAmount;
                           } else {
                               
                               if ($gPlan['dual_only'] == '0' && $elecDualOnly == '0') {
                                $Plans['comboPlan_id'][$k]['electricity_id'] = $ePlan['id'];
                                $Plans['comboPlans'][$k]['electricity'] = $ePlan;
                                $Plans['comboPlan_id'][$k]['gas_id'] = $gPlan['id'];
                                $Plans['comboPlans'][$k]['gas'] = $gPlan;
                               }
                           }
                       }
                   
               }
           }
       }
       return $Plans;
   }  

   static function getProvidersData($allProvider,$elecProvider,$gasProvider,$energy){
    $finalData=[];
    if($energy == 'electricitygas'){
        $finalProviders=array_merge($elecProvider->toArray(),$gasProvider->toArray());
    }elseif($energy == 'electricity'){
        $finalProviders=$elecProvider->toArray();
    }else{
        $finalProviders=$gasProvider->toArray();
    }
     
      foreach($finalProviders as $provider){
        $data = array_filter($allProvider->toArray(), function ($value)use($provider) {
            return $value['relational_user_id'] == $provider['user_id'];
        });
          $data= array_values($data);
         
          $finalData[$provider['user_id']]['user_id'] = $provider['user_id'];
          $finalData[$provider['user_id']]['user_id'] = $provider['user_id'];
          $finalData[$provider['user_id']]['name'] = isset($data[0]['providers']['name'])?($data[0]['providers']['name']):"";
          $finalData[$provider['user_id']]['legal_name'] = isset($data[0]['providers']['legal_name'])?($data[0]['providers']['legal_name']):'';
          $finalData[$provider['user_id']]['logo'] = isset($data[0]['providers']['logo'][0]['name'])?$data[0]['providers']['logo'][0]['name']:"";
          $finalData[$provider['user_id']]['gas_allow'] = $provider['is_gas_only'];
          $finalData[$provider['user_id']]['telecom_allow'] = $provider['is_telecom'];
          $finalData[$provider['user_id']]['send_plan_allow'] = $provider['is_send_plan'];
          $finalData[$provider['user_id']]['apply_now_content_status'] = isset($data[0]['providers']['content']['status'])?$data[0]['providers']['content']['status']:0;
      } 
      return $finalData;
   }



   static function getSolorByBackPrice($solor_usage, $solor_price, $is_both = 0, $selected_tariff_type = null)
   {
       $solor_bb_price = 0;
       if ($selected_tariff_type == null) {
           $selected_tariff_type = 'normal';
       }
       //check if solar rates exists or not
       if (count($solor_price) > 0 && count(array_keys(array_column($solor_price, 'status'), 1)) > 0) {
           //find the active solor rate
           $active_rates = array_keys(array_column($solor_price, 'status'), 1);
           $normal_rate = [];
           $premium_rate = [];
           $normal_price = 0;
           $premium_price = 0;
           foreach ($active_rates as  $value) {
               if ($solor_price[$value]['type'] == 2)
                   $premium_rate = $solor_price[$value];
               else
                   $normal_rate = $solor_price[$value];
           }
           if ($is_both) {

               if ($selected_tariff_type == 'premium') {
                   if (!empty($normal_rate) && count($normal_rate) > 0)
                       $normal_price = ($solor_usage * $normal_rate['solar_price']) / 100;
                   if (!empty($premium_rate) && count($premium_rate) > 0)
                       $premium_price = ($solor_usage * $premium_rate['solar_price']) / 100;

                   $solor_bb_price = $normal_price + $premium_price;
               } elseif ($selected_tariff_type == 'normal') {
                   if (!empty($normal_rate) && count($normal_rate) > 0)
                       $normal_price = ($solor_usage * $normal_rate['solar_price']) / 100;
                   $solor_bb_price = $normal_price + $premium_price;
               }
           } elseif ($selected_tariff_type == 'normal' && count($normal_rate) > 0) {
               $solor_bb_price = ($solor_usage * $normal_rate['solar_price']) / 100;
           } elseif ($selected_tariff_type == 'premium') {
               if (!empty($premium_rate) && count($premium_rate) > 0) {
                   $solor_bb_price = ($solor_usage * $premium_rate['solar_price']) / 100;
               } else {
                   if (!empty($normal_rate) && count($normal_rate) > 0)
                       $solor_bb_price = ($solor_usage * $normal_rate['solar_price']) / 100;
               }
           }
       }
       return $solor_bb_price;
   }
}
