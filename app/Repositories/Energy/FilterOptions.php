<?php

namespace App\Repositories\Energy;

use DB;
use Storage;
use App\Models\Provider;
use Illuminate\Validation\Rule;
use Session;
use Auth;
Class FilterOptions
{
    static function getPlanFilterOptions($plans, $energy_type = null)
    {
        try {
          
            $filters=[];
            /** Global function defined for provider list **/
           // $providersList = self::getProviderList();
             $providersList =  Provider::getProviders(1);
         
            //declare empty array of all filter options;
            
            if (isset($plans['electricity']) && count($plans['electricity']) > 0 && $plans['electricity'] != 0 && ($energy_type == 'electricity' || $energy_type == 'electricitygas')) {

                $filters['electricity']['provider_options'] = [];
                $filters['electricity']['contract_length_options'] = [];
                $filters['electricity']['billing_options'] = [];
                $filters['electricity']['discount_options'] = [];
                $filters['electricity']['your_preference']['no_exit_fee_option'] = array('preference_value' => 'no_exit_fee_option', 'preference_name' => 'No Exit Fee', 'count' => 0);

                $filters['electricity']['your_preference']['solar_compatible'] = array('preference_value' => 'solar_compatible', 'preference_name' => 'Solar Compatible', 'count' => 0);
                $filters['electricity']['your_preference']['green_option'] = array('preference_value' => 'green_option', 'preference_name' => 'Green Option', 'count' => 0);
                //get electricity providers and set in current provider for use in filters only electricity
                $filters['electricity']['selected_elec_curr_provider'] = $providersList; //added
                //$filters['electricity']['selected_elec_distributors'] = []; //added
                // if electricity plans are fetched
                
                foreach ($plans['electricity'] as $plan) {
                   
                    $filters['electricity'] = self::fetchFiltersFromPlan($plan, $filters['electricity']);
                    
                }
                
            }
            //if gas plans are fetched
            if (isset($plans['gas']) && count($plans['gas']) > 0 && $plans['gas'] != 0 && ($energy_type == 'gas' || $energy_type == 'electricitygas')) {
                $filters['gas']['provider_options'] = [];
                $filters['gas']['contract_length_options'] = [];
                $filters['gas']['billing_options'] = [];
                $filters['gas']['discount_options'] = [];
                $filters['gas']['your_preference']['no_exit_fee_option'] = array('preference_value' => 'no_exit_fee_option', 'preference_name' => 'No Exit Fee', 'count' => 0);
                //get gas providers and set in current provider for use in filters only electrgasicity
                $filters['gas']['selected_gas_curr_provider'] = $providersList; //added
                $filters['gas']['selected_gas_distributors'] = []; //added
                //if gas plans are fetched
                foreach ($plans['gas'] as $plan) {
                    //if ($plan['provider']['gas_allow'] == 'yes') {
                        //only allow if provider allow single gas only plan
                       
                        $filters['gas'] = self::fetchFiltersFromPlan($plan, $filters['gas']);
                       
                       // dd($filters['gas']);
                    //}
                }
            }
         
            if(count($plans['combined_plans'])){

                $filters['electricitygas']['provider_options'] = [];
                $filters['electricitygas']['contract_length_options'] = [];
                $filters['electricitygas']['billing_options'] = [];
                $filters['electricitygas']['discount_options'] = [];
                //get electricity providers and set in current provider for use in filters for combine case
                $filters['electricitygas']['selected_elec_curr_provider'] = $providersList;  //added
                //get gas providers and set in current provider for use in filters
                $filters['electricitygas']['selected_gas_curr_provider'] = $providersList;  //added
                $filters['electricitygas']['selected_elec_distributors'] = [];  //added
                $filters['electricitygas']['selected_gas_distributors'] = [];  //added
                $filters['electricitygas']['your_preference']['no_exit_fee_option'] = array('preference_value' => 'no_exit_fee_option', 'preference_name' => 'No Exit Fee', 'count' => 0);
                $filters['electricitygas']['your_preference']['solar_compatible'] = array('preference_value' => 'solar_compatible', 'preference_name' => 'Solar Compatible', 'count' => 0);
                $filters['electricitygas']['your_preference']['green_option'] = array('preference_value' => 'green_option', 'preference_name' => 'Green Option', 'count' => 0);
              
                foreach ($plans['combined_plans'] as $plan) {
                   
                    $temp_provider_options = $filters['electricitygas']['provider_options'];
                    $temp_contract_length_options = $filters['electricitygas']['contract_length_options'];
                    $temp_billing_options = $filters['electricitygas']['billing_options'];
                    $temp_no_exit_fee_option = $filters['electricitygas']['your_preference']['no_exit_fee_option'];
                    $filters['electricitygas'] = self::fetchFiltersFromPlan($plan['electricity'], $filters['electricitygas']);
                    $filters['electricitygas'] = self::fetchFiltersFromPlan($plan['gas'], $filters['electricitygas']);
                    
                    //change filters again if both plan have different different option value
                    if ($plan['electricity']['provider_id'] != $plan['gas']['provider_id'])
                        $filters['electricitygas']['provider_options'] = $temp_provider_options;
                    else {
                        //if provider is same the count should be incremented for both elec and gas but we have to treat it as a one plan so minus 1 from that provider count
                        $provider_ids = array_column($filters['electricitygas']['provider_options'], 'provider_id');
                        $required_key = array_search($plan['electricity']['provider_id'], $provider_ids);
                        $filters['electricitygas']['provider_options'][$required_key]['count'] = $filters['electricitygas']['provider_options'][$required_key]['count'] - 1;
                    }
        
                    if ($plan['electricity']['contract_length'] != $plan['gas']['contract_length'])
                        $filters['electricitygas']['contract_length_options'] = $temp_contract_length_options;
                    else {
                        //if provider is same the count should be incremented for both elec and gas but we have to treat it as a one plan so minus 1 from that provider count
                        $contract_lengths = array_column($filters['electricitygas']['contract_length_options'], 'contract_length');
                        $required_key = array_search($plan['electricity']['contract_length'], $contract_lengths);
                        $filters['electricitygas']['contract_length_options'][$required_key]['count'] = $filters['electricitygas']['contract_length_options'][$required_key]['count'] - 1;
                    }
        
                    if ($plan['electricity']['billing_options'] != $plan['gas']['billing_options'])
                        $filters['electricitygas']['billing_options'] = $temp_billing_options;
                    else {
                        //if provider is same the count should be incremented for both elec and gas but we have to treat it as a one plan so minus 1 from that provider count
                        $billing_options = array_column($filters['electricitygas']['billing_options'], 'billing_option');
                        $required_key = array_search($plan['electricity']['billing_options'], $billing_options);
                        $filters['electricitygas']['billing_options'][$required_key]['count'] = $filters['electricitygas']['billing_options'][$required_key]['count'] - 1;
                    }
        
                    if (isset($plan['electricity']['exit_fee_option']) && isset($plan['gas']['exit_fee_option']) && $plan['electricity']['exit_fee_option'] != $plan['gas']['exit_fee_option'])
                        $filters['electricitygas']['your_preference']['no_exit_fee_option'] = $temp_no_exit_fee_option;
                    else {
                        //if provider is same the count should be incremented for both elec and gas but we have to treat it as a one plan so minus 1 from that provider count
                        $filters['electricitygas']['your_preference']['no_exit_fee_option']['count'] = $filters['electricitygas']['your_preference']['no_exit_fee_option']['count'] - 1;
                    }
        
                    //discount section starts from here
                    // 1. Pay day discount
                    if (isset($plan['electricity']['applied_pay_day_discount_usage']) && isset($plan['gas']['applied_pay_day_discount_usage']) && $plan['electricity']['applied_pay_day_discount_usage'] != $plan['gas']['applied_pay_day_discount_usage']) {
                        $pay_day_discount_usage = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('pay_day_discount', $pay_day_discount_usage);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_pay_day_discount_usage']) && !isset($plan['gas']['applied_pay_day_discount_usage']) && $plan['electricity']['applied_pay_day_discount_usage'] == 'yes') {
                        $pay_day_discount_usage = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('pay_day_discount', $pay_day_discount_usage);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['gas']['applied_pay_day_discount_usage']) && !isset($plan['electricity']['applied_pay_day_discount_usage']) && $plan['gas']['applied_pay_day_discount_usage'] == 'yes') {
                        $pay_day_discount_usage = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('pay_day_discount', $pay_day_discount_usage);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_pay_day_discount_usage']) && isset($plan['gas']['applied_pay_day_discount_usage']) && $plan['electricity']['applied_pay_day_discount_usage'] == $plan['gas']['applied_pay_day_discount_usage']) {
                        //treat both plans as a one plan so minus 1 from count
                        $pay_day_discount_usage = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('pay_day_discount', $pay_day_discount_usage);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    }
        
                    // 2. Guarateed discount
                    if (isset($plan['electricity']['applied_gurrented_discount_usage']) && isset($plan['gas']['applied_gurrented_discount_usage']) && $plan['electricity']['applied_gurrented_discount_usage'] != $plan['gas']['applied_gurrented_discount_usage']) {
                        $gurrented_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('gurrented_discount', $gurrented_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_gurrented_discount_usage']) && !isset($plan['gas']['applied_gurrented_discount_usage']) && $plan['electricity']['applied_gurrented_discount_usage'] == 'yes') {
                        $gurrented_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('gurrented_discount', $gurrented_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['gas']['applied_gurrented_discount_usage']) && !isset($plan['electricity']['applied_gurrented_discount_usage']) && $plan['gas']['applied_gurrented_discount_usage'] == 'yes') {
                        $gurrented_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('gurrented_discount', $gurrented_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_gurrented_discount_usage']) && isset($plan['gas']['applied_gurrented_discount_usage']) && $plan['electricity']['applied_gurrented_discount_usage'] == $plan['gas']['applied_gurrented_discount_usage']) {
                        //treat both plans as a one plan so minus 1 from count
                        $gurrented_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('gurrented_discount', $gurrented_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    }
        
                    // 3. Direct debit discount
                    if (isset($plan['electricity']['applied_direct_debit_discount_usage']) && isset($plan['gas']['applied_direct_debit_discount_usage']) && $plan['electricity']['applied_direct_debit_discount_usage'] != $plan['gas']['applied_direct_debit_discount_usage']) {
                        $direct_debit_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('direct_debit_discount', $direct_debit_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_direct_debit_discount_usage']) && !isset($plan['gas']['applied_direct_debit_discount_usage']) && $plan['electricity']['applied_direct_debit_discount_usage'] == 'yes') {
                        $direct_debit_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('direct_debit_discount', $direct_debit_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['gas']['applied_direct_debit_discount_usage']) && !isset($plan['electricity']['applied_direct_debit_discount_usage']) && $plan['gas']['applied_direct_debit_discount_usage'] == 'yes') {
                        $direct_debit_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('direct_debit_discount', $direct_debit_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_direct_debit_discount_usage']) && isset($plan['gas']['applied_direct_debit_discount_usage']) && $plan['electricity']['applied_direct_debit_discount_usage'] == $plan['gas']['applied_direct_debit_discount_usage']) {
                        //treat both plans as a one plan so minus 1 from count
                        $direct_debit_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('direct_debit_discount', $direct_debit_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    }
        
                    // 4. Dual fuel discount
                    if (isset($plan['electricity']['applied_dual_fuel_discount_usage']) && isset($plan['gas']['applied_dual_fuel_discount_usage']) && $plan['electricity']['applied_dual_fuel_discount_usage'] != $plan['gas']['applied_dual_fuel_discount_usage']) {
                        $dual_fuel_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('dual_fuel_discount', $dual_fuel_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_dual_fuel_discount_usage']) && !isset($plan['gas']['applied_dual_fuel_discount_usage']) && $plan['electricity']['applied_dual_fuel_discount_usage'] == 'yes') {
                        $dual_fuel_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('dual_fuel_discount', $dual_fuel_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['gas']['applied_dual_fuel_discount_usage']) && !isset($plan['electricity']['applied_dual_fuel_discount_usage']) && $plan['gas']['applied_dual_fuel_discount_usage'] == 'yes') {
                        $dual_fuel_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('dual_fuel_discount', $dual_fuel_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    } elseif (isset($plan['electricity']['applied_dual_fuel_discount_usage']) && isset($plan['gas']['applied_dual_fuel_discount_usage']) && $plan['electricity']['applied_dual_fuel_discount_usage'] == $plan['gas']['applied_dual_fuel_discount_usage']) {
                        //treat both plans as a one plan so minus 1 from count
                        $dual_fuel_discount = array_column($filters['electricitygas']['discount_options'], 'discount_value');
                        $required_key = array_search('dual_fuel_discount', $dual_fuel_discount);
                        $filters['electricitygas']['discount_options'][$required_key]['count'] = $filters['electricitygas']['discount_options'][$required_key]['count'] - 1;
                    }
                }
            }
           
            
            return $filters;
        } catch (\Exception $e) {
            $response = ['status' => false, 'errors' => 'Something went wrong, Please try again later'];
            $status = 400;
            return response()->json($response, $status);
        }
    }


    static function fetchFiltersFromPlan($plan, $filters)
    {
        try {
            
            $provider_ids = array_column($filters['provider_options'], 'provider_id');
            
           
            $contract_lengths = array_column($filters['contract_length_options'], 'contract_length');
            $available_billing_options = array_column($filters['billing_options'], 'billing_option');
            $available_discounts = array_column($filters['discount_options'], 'discount_value');
           
            $your_preferences = array_column($filters['your_preference'], 'preference_value');
            //provider filter
            
            if (isset($plan['provider_id']) && !in_array($plan['provider_id'], $provider_ids)) {
                
                $filters['provider_options'][] = array('provider_id' => $plan['provider_id'], 'provider_name' => isset($plan['provider']['name'])?$plan['provider']['name']:"", 'count' => 1);
            } else {
               
                $required_key = array_search($plan['provider_id'], $provider_ids);
                $filters['provider_options'][$required_key]['count'] = $filters['provider_options'][$required_key]['count'] + 1;
                
            }
           
            //contract length filter
            if (!in_array($plan['contract_length'], $contract_lengths)) {
                $filters['contract_length_options'][] = array('contract_length' => $plan['contract_length'], 'count' => 1);
            } else {
                $required_key = array_search($plan['contract_length'], $contract_lengths);
                $filters['contract_length_options'][$required_key]['count'] = $filters['contract_length_options'][$required_key]['count'] + 1;
            }

            //billing options filter
            if (!in_array($plan['billing_options'], $available_billing_options)) {
                $filters['billing_options'][] = array('billing_option' => $plan['billing_options'], 'count' => 1);
            } else {
                $required_key = array_search($plan['billing_options'], $available_billing_options);
                $filters['billing_options'][$required_key]['count'] = $filters['billing_options'][$required_key]['count'] + 1;
            }

            //pay day discount filter
            if ((isset($plan['applied_pay_day_discount_usage']) && $plan['applied_pay_day_discount_usage'] == 'yes') || (isset($plan['applied_pay_day_discount_supply']) && $plan['applied_pay_day_discount_supply'] == 'yes')) {
                if (!in_array('pay_day_discount', $available_discounts)) {
                    $filters['discount_options'][] = array('discount_value' => 'pay_day_discount', 'discount_name' => 'Pay On Time', 'count' => 1);
                } else {
                    $required_key = array_search('pay_day_discount', $available_discounts);
                    $filters['discount_options'][$required_key]['count'] = $filters['discount_options'][$required_key]['count'] + 1;
                }
            }

            //guranteed discount filter
            if ((isset($plan['applied_gurrented_discount_usage']) && $plan['applied_gurrented_discount_usage'] == 'yes') || (isset($plan['applied_gurrented_discount_supply']) && $plan['applied_gurrented_discount_supply'] == 'yes')) {
                if (!in_array('gurrented_discount', $available_discounts)) {
                    $filters['discount_options'][] = array('discount_value' => 'gurrented_discount', 'discount_name' => 'Guarantee', 'count' => 1);
                } else {
                    $required_key = array_search('gurrented_discount', $available_discounts);
                    $filters['discount_options'][$required_key]['count'] = $filters['discount_options'][$required_key]['count'] + 1;
                }
            }

            //direct debit discount filter
            if ((isset($plan['applied_direct_debit_discount_usage']) && $plan['applied_direct_debit_discount_usage'] == 'yes') || (isset($plan['applied_direct_debit_discount_supply']) && $plan['applied_direct_debit_discount_supply'] == 'yes')) {
                if (!in_array('direct_debit_discount', $available_discounts)) {
                    $filters['discount_options'][] = array('discount_value' => 'direct_debit_discount', 'discount_name' => 'Direct Debit', 'count' => 1);
                } else {
                    $required_key = array_search('direct_debit_discount', $available_discounts);
                    $filters['discount_options'][$required_key]['count'] = $filters['discount_options'][$required_key]['count'] + 1;
                }
            }

            //dual fuel discount filter
            if ((isset($plan['applied_dual_fuel_discount_usage']) && $plan['applied_dual_fuel_discount_usage'] == 'yes') || (isset($plan['applied_dual_fuel_discount_supply']) && $plan['applied_dual_fuel_discount_supply'] == 'yes')) {
                if (!in_array('dual_fuel_discount', $available_discounts)) {
                    $filters['discount_options'][] = array('discount_value' => 'dual_fuel_discount', 'discount_name' => 'Dual Fuel', 'count' => 1);
                } else {
                    $required_key = array_search('dual_fuel_discount', $available_discounts);
                    $filters['discount_options'][$required_key]['count'] = $filters['discount_options'][$required_key]['count'] + 1;
                }
            }

            //solar compatible filter
            if (isset($plan['solar_compatible']) && $plan['solar_compatible'] == 'yes' && $plan['energy_type'] == 'electricity') {
                if (in_array('solar_compatible', $your_preferences)) {
                    $filters['your_preference']['solar_compatible']['count'] = $filters['your_preference']['solar_compatible']['count'] + 1;
                }
            }

            //green option filter
            if (isset($plan['green_options']) && $plan['green_options'] == 'yes' && $plan['energy_type'] == 'electricity') {
                if (in_array('green_option', $your_preferences)) {
                    $filters['your_preference']['green_option']['count'] = $filters['your_preference']['green_option']['count'] + 1;
                }
            }

            //No Exit Fee option filter
            if (isset($plan['exit_fee_option']) && $plan['exit_fee_option'] == 'no') {
                if (in_array('no_exit_fee_option', $your_preferences)) {
                    $filters['your_preference']['no_exit_fee_option']['count'] = $filters['your_preference']['no_exit_fee_option']['count'] + 1;
                }
            }

            return $filters;
        } catch (\Exception $e) {
            $response = ['status' => false, 'errors' => $e->getMessage(),'line'=>$e->getLine()];
            $status = 400;
            return response()->json($response, $status);
        }
    }

    static function applyFilters($responseData,$filterRequest){
        if (isset($filterRequest['selected_sort_by']) && !empty($filterRequest['selected_sort_by']) && $filterRequest['selected_sort_by']== 'lowest_price_excluding_discounts') {
					
            if (isset($responseData['electricity']) && count($responseData['electricity']) > 0) {
                if (!empty($responseData['electricity'])) {
                    
                    usort($responseData['electricity'], function ($a, $b) {
                        if ($a['expected_bill_amount'] - $b['expected_bill_amount'] > 0) {
                            return true;
                        } elseif ($a['expected_bill_amount'] - $b['expected_bill_amount'] == 0) {
                            return $a['expected_discounted_bill_amount'] - $b['expected_discounted_bill_amount'];
                        } else {
                            return false;
                        }
                    });
                }
            }
           
            if (isset($responseData['gas']) && count($responseData['gas']) > 0) {
                if (!empty($responseData['gas'])) {
                    usort($responseData['gas'], function ($a, $b) {
                        if ($a['expected_gas_bill_amount'] - $b['expected_gas_bill_amount'] > 0) {
                            return true;
                        } elseif ($a['expected_gas_bill_amount'] - $b['expected_gas_bill_amount'] == 0) {
                            return $a['expected_discounted_gas_bill_amount'] - $b['expected_discounted_gas_bill_amount'];
                        } else {
                            return false;
                        }
                    });
                }
            }
        }

        if (isset($filterRequest['selected_providers']) && (!empty($filterRequest['selected_providers']) || count($filterRequest['selected_providers']) > 0)) {
            if (isset($responseData['electricity']) && count($responseData['electricity']) > 0 && $responseData['electricity'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['electricity'] = array_filter($responseData['electricity'], function ($arr) use ($filterRequest) {
                    if (in_array($arr['provider_id'], $filterRequest['selected_providers']))
                        return $arr;
                });
            }
            if (isset($responseData['gas']) && count($responseData['gas']) > 0 && $responseData['gas'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['gas'] = array_filter($responseData['gas'], function ($arr) use ($filterRequest) {
                    if (in_array($arr['provider_id'],$filterRequest['selected_providers']))
                        return $arr;
                });
            }
            
        }
        if (isset($filter_request['selected_contract_lengths']) && !empty($filter_request['selected_contract_lengths']) && count($filter_request['selected_contract_lengths']) > 0) {
            if (isset($responseData['electricity']) && count($responseData['electricity']) > 0 && $responseData['electricity'] != 0) {
                //filter only those plan whose provider is selected
            
                $responseData['electricity'] = array_filter($responseData['electricity'], function ($arr) use ($filter_request) {
                    if (in_array($arr['contract_length'], $filter_request['selected_contract_lengths']))
                        return $arr;
                });
            }
                
            if (isset($responseData['gas']) && count($responseData['gas']) > 0 && $responseData['gas'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['gas'] = array_filter($responseData['gas'], function ($arr) use ($filter_request) {
                    if (in_array($arr['contract_length'], $filter_request['selected_contract_lengths']))
                
                        return $arr;
                });
            }
        }

        if (isset($filter_request['selected_billing_options']) && !empty($filter_request['selected_billing_options']) && count($filter_request['selected_billing_options']) > 0) {
            if (isset($responseData['electricity']) && count($responseData['electricity']) > 0 && $responseData['electricity'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['electricity'] = array_filter($responseData['electricity'], function ($arr) use ($filter_request) {
                    if (in_array($arr['billing_options'], $filter_request['selected_billing_options']))
                        return $arr;
                });
            }
            if (isset($responseData['gas']) && count($responseData['gas']) > 0 && $responseData['gas'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['gas'] = array_filter($responseData['gas'], function ($arr) use ($filter_request) {
                    if (in_array($arr['billing_options'], $filter_request['selected_billing_options']))
                        return $arr;
                });
            }
            if (isset($responseData['combined_plans']) && count($responseData['combined_plans']) > 0) {
                //filter only those plan whose provider is selected
                $responseData['combined_plans'] = array_filter($responseData['combined_plans'], function ($arr) use ($filter_request) {
                    if (in_array($arr['electricity']['billing_options'], $filter_request['selected_billing_options']) && in_array($arr['gas']['billing_options'],$filter_request['selected_billing_options']))
                        return $arr;
                });
            }
        }

        if (isset($filter_request['selected_green_option']) && !empty($filter_request['selected_green_option']) && $filter_request['selected_green_option'] == 'yes') {
            if (isset($responseData['electricity']) && count($responseData['electricity']) > 0 && $responseData['electricity'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['electricity'] = array_filter($responseData['electricity'], function ($arr) use ($filter_request) {
                    if (isset($arr['green_options']) && $arr['green_options'] == 1)
                        return $arr;
                });
            }
            if (isset($responseData['combined_plans']) && count($responseData['combined_plans']) > 0) {
                //filter only those plan whose provider is selected
                $responseData['combined_plans'] = array_filter($responseData['combined_plans'], function ($arr) use ($filter_request) {
                    if (isset($arr['electricity']['green_options']) && $arr['electricity']['green_options'] == 1)
                        return $arr;
                });
            }
        }

        if (isset($filter_request['selected_discount_types']) && !empty($filter_request['selected_discount_types']) && count($filter_request['selected_discount_types']) > 0) {
            if (isset($responseData['electricity']) && count($responseData['electricity']) > 0 && $responseData['electricity'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['electricity'] = array_filter($responseData['electricity'], function ($arr) use ($filter_request) {
                    $flag = 1;
                    if (in_array('pay_day_discount', $filter_request['selected_discount_types'])) {
                        if (isset($arr['applied_pay_day_discount_usage']) && $arr['applied_pay_day_discount_usage'] == 'yes')
                            $flag = 1;
                        else
                            return false;
                    }
                    if (in_array('gurrented_discount', $filter_request['selected_discount_types'])) {
                        if (isset($arr['applied_gurrented_discount_usage']) && $arr['applied_gurrented_discount_usage'] == 'yes')
                            $flag = 1;
                        else
                            return false;
                    }
                    if (in_array('direct_debit_discount', $filter_request['selected_discount_types'])) {
                        if (isset($arr['applied_direct_debit_discount_usage']) && $arr['applied_direct_debit_discount_usage'] == 'yes')
                            $flag = 1;
                        else
                            return false;
                    }
                    if ($flag == 1)
                        return $arr;
                    else
                        return false;
                });
            }
            if (isset($responseData['gas']) && count($responseData['gas']) > 0 && $responseData['gas'] != 0) {
                //filter only those plan whose provider is selected
                $responseData['gas'] = array_filter($responseData['gas'], function ($arr) use ($filter_request) {
                    $flag = 1;
                    if (in_array('pay_day_discount',$filter_request['selected_discount_types'])) {
                        if (isset($arr['applied_pay_day_discount_usage']) && $arr['applied_pay_day_discount_usage'] == 'yes')
                            $flag = 1;
                        else
                            return false;
                    }
                    if (in_array('gurrented_discount', $filter_request['selected_discount_types'])) {
                        if (isset($arr['applied_gurrented_discount_usage']) && $arr['applied_gurrented_discount_usage'] == 'yes')
                            $flag = 1;
                        else
                            return false;
                    }
                    if (in_array('direct_debit_discount',$filter_request['selected_discount_types'])) {
                        if (isset($arr['applied_direct_debit_discount_usage']) && $arr['applied_direct_debit_discount_usage'] == 'yes')
                            $flag = 1;
                        else
                            return false;
                    }
                    if ($flag == 1)
                        return $arr;
                    else
                        return false;
                });
            }

            if (isset($filter_request['selected_exit_fee']) && !empty($filter_request['selected_exit_fee']) && $filter_request['selected_exit_fee'] == 1) {
						
                if (isset($responseData['electricity']) && count($responseData['electricity']) > 0 && $responseData['electricity'] != 0) {
                    //filter only those plan whose provider is selected
                    $responseData['electricity'] = array_filter($responseData['electricity'], function ($arr) use ($filter_request) {
                        if (isset($arr['exit_fee_option']) && $arr['exit_fee_option'] == 0)
                            return $arr;
                    });
                }
                if (isset($responseData['gas']) && count($responseData['gas']) > 0 && $responseData['gas'] != 0) {
                    //filter only those plan whose provider is selected
                    $responseData['gas'] = array_filter($responseData['gas'], function ($arr) use ($filter_request) {
                        if (isset($arr['exit_fee_option']) && $arr['exit_fee_option'] ==0)
                            return $arr;
                    });
                }
                if (isset($responseData['combined_plans']) && count($responseData['combined_plans']) > 0) {
                    //filter only those plan whose provider is selected
                    $responseData['combined_plans'] = array_filter($responseData['combined_plans'], function ($arr) use ($filter_request) {
                        if (isset($arr['electricity']['exit_fee_option']) && $arr['electricity']['exit_fee_option'] == 0)
                            return $arr;
                    });
                }
            }
           
        }
        return $responseData;
    }
}
