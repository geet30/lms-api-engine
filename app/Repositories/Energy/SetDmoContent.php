<?php

namespace App\Repositories\Energy;

use DB;
use Storage;
use App\Models\Provider;
use Illuminate\Validation\Rule;
use Session;
use Auth;
Class SetDmoContent
{
     static function setContent($dmoData,$requestData){
        $dmoFilterRate= [];
        $dmoArray=[];
        $masterdmoAttributs = $dmoData['masterDmoAttributes'];
        $planDmoAttributs = $dmoData['planDmoAttributes'];
            foreach($dmoData['planData'] as $key => $data){
                foreach($data['rate'] as $rate){

                    if($requestData->has('is_agent')&& $requestData->is_agent==1){
                        $filterData = array_filter($rate['plan_dmo_content'], function ($value) {
                            if($value['type'] == 1){
                                if($value['variant'] == 1 || $value['variant'] == 2 ){
                                    if($value['dmo_content_status'] == 1){
                                        return $value;
                                    }
                                    
                                }
                            }
                       });
                        
                    }
                   
                    if(empty($filterData)){
                        $filterData = array_filter($rate['plan_dmo_content'], function ($value) {
                            if($value['type'] == 1){
                                if($value['variant'] == 1 || $value['variant'] == 3 ){
                                    return $value;
                                }
                            }
                       });
                    }
                    if(isset($filterData[0]['dmo_content_status']) && $filterData[0]['dmo_content_status'] == 1){
                        
                        $dmoContent = $filterData[0]['dmo_vdo_content'];
                        $getStaticContent = array_filter($rate['plan_dmo_content'], function ($value) {
                            if($value['type'] == 1){
                                if($value['variant'] == 3 ){
                                    return $value;
                                }
                            }
                       });
                       $getStaticContent= array_values($getStaticContent);
                       if(isset($getStaticContent[0]['dmo_content_status']) &&  $getStaticContent[0]['dmo_content_status'] == 1){ 

                            $Dmorate= $getStaticContent[0];
                            if($Dmorate["with_conditional"] == 1){
                                $withConditional = 'Less Than';
                            }elseif($Dmorate["with_conditional"] == 2){
                                $withConditional = 'More Than';
                            }else{
                                $withConditional = 'Equal To';
                            }

                            if($Dmorate["without_conditional"] == 1){
                                $withOutConditional = 'Less Than';
                            }elseif($Dmorate["without_conditional"] == 2){
                                $withOutConditional = 'More Than';
                            }else{
                                $withOutConditional = 'Equal To';
                            }

                            if ($Dmorate["without_conditional_value"] != "" && $Dmorate["without_conditional"] != 3) {
                                $dmoFilterRate['static_without_conditional_low_price_cap_percentage'] = $Dmorate["without_conditional_value"] . " " . $withOutConditional;
                            } else {
                                $dmoFilterRate['static_without_conditional_low_price_cap_percentage'] = $withOutConditional;
                            }
                            if ($Dmorate["with_conditional_value"] != "" && $Dmorate["with_conditional"] != 3) {
                                $dmoFilterRate['static_low_price_cap_percentage'] = $Dmorate["with_conditional_value"] . " " . $withConditional;
                            } else {
                                $dmoFilterRate['static_low_price_cap_percentage'] = $withConditional;
                            }
                                if ($Dmorate["without_conditional_value"] != "" && $Dmorate['without_conditional'] != 3) {
                                    $getContentData['difference_without_conditional_discount'] = $Dmorate["without_conditional_value"];
                                } else {
                                    $getContentData['difference_without_conditional_discount']  = "";
                                }
                                if ($Dmorate["with_conditional_value"] != "" && $Dmorate['with_conditional'] != 3) {
                                    $getContentData['difference_with_conditional_discount'] = $Dmorate["with_conditional_value"];
                                } else {
                                    $getContentData['difference_with_conditional_discount']  = "";
                                }
                                $getContentData['lowest_possible_annual_cost'] = $Dmorate['lowest_annual_cost'];
                                $get_content_data['less_more']  = $Dmorate['without_conditional'];


                                if($rate["without_conditional_value"] != "" && $rate['without_conditional'] != 3)
                                {
                                    $dmoPlanName = $rate["without_conditional_value"]." ".$rate["without_conditional"];
                                    /*for dmo static content manage backend*/
                                    $dmoFilterrate['front_without_conditional_low_price_cap_percentage'] = $rate["without_conditional_value"]." ".$rate["without_conditional"];
                                }else{
                                    $dmoPlanName = "Equal To";
                                }
                            
                                 
                        }else{
                           $content =  self::calculateDmoPrice($rate,$dmoData['billData'],$dmoData['dmoVdoprices'],$rate['type'],$requestData);
                           $getContentData['difference_without_conditional_discount'] =$content['difference_without_conditional_discount'];
                            $getContentData['difference_with_conditional_discount'] = $content['difference_with_conditional_discount'];
                            $getContentData['lowest_possible_annual_cost'] = $content['lowest_possible_annual_cost'];
                            $dmoPlanName = $content['dmo_content_plan_name'];
                            $getContentData['less_more'] = $content['less_more_text'];
                        }      

                        if($requestData->dmo_vdo_type == 0){
                            $dmoBelowPlanName = "Equal to reference price";
                        }else{
                            $dmoBelowPlanName = "Equal to the Victorian Default Offer";
                        }
                       
                      
                        $getContentData['property_type'] = getPrpertyType($dmoData['leadData']['property_type']);
                        //$getContentData['total_consumption'] = $dmoData['dmoVdoprices']['annual_usage'];
                        $getContentData['tariff_type'] = $rate['type'];
                        $getContentData['distributor'] = $dmoData['distributorData']->name;
                        $getContentData['plan_name']  = $data['plan_name'];
                        $getContentData['provider_name']  = isset($data['planData'][0]['provider']['legal_name'])?$data['planData'][0]['provider']['legal_name']:''; 
                        if (!empty($dmoContent)) {
                            
                            $dmoText = str_replace($planDmoAttributs->toArray(), $getContentData, $dmoContent);
                            $dmoArray[$data['id']][$rate['distributor_id']]['status']=1;
                            $dmoArray[$data['id']][$rate['distributor_id']]['message']='DMO-VDO-Content found successfully';
                            $dmoArray[$data['id']][$rate['distributor_id']]['tariff_name']='single';
                            $dmoArray[$data['id']][$rate['distributor_id']]['dmo_text']=$dmoText;
                            $dmoArray[$data['id']][$rate['distributor_id']]['dmo_plan_name']=$dmoPlanName;
                            $dmoArray[$data['id']][$rate['distributor_id']]['dmo_below_plan_name']=$dmoBelowPlanName;
                            
                        }
                       
                    }else{
                       
                        $content =  self::calculateDmoPrice($rate,$dmoData['billData'],$dmoData['dmoVdoprices'],$rate['type'],$requestData);
                        
                        if($dmoData['masterDmoContent']){
                            $masterContent= $dmoData['masterDmoContent']->toArray();
                        }else{
                            $masterContent=  $dmoData['masterDmoContent']=[];
                        }
                        $filterData = array_filter($masterContent, function ($value)use($content) {
                                if( $value['variant'] == $content['masterContentType'] ){
                                    return $value;
                                }
                       });
                       $filterData = array_values($filterData);
                      
                       if(isset($filterData[0]['dmo_vdo_content']) &&  $filterData[0]['dmo_vdo_content']!=''){
                      
                            $getContentData['difference_without_conditional_discount'] =$content['difference_without_conditional_discount'];
                            $getContentData['difference_with_conditional_discount'] = $content['difference_with_conditional_discount'];
                            $getContentData['lowest_possible_annual_cost'] = $content['lowest_possible_annual_cost'];
                            $getContentData['less/more'] = $content['less_more_text'];
                            $dmoPlanName = $content['dmo_content_plan_name'];
                            $getContentData['tariff_type'] = $rate['type'];
                            $getContentData['distributor'] = $dmoData['distributorData']->name;
                            $getContentData['plan_name']  = $data['plan_name'];
                            $getContentData['provider_name']  = isset($data['provider']['legal_name'])?$data['provider']['legal_name']:''; 
                            $dmoContent = $filterData[0]['dmo_vdo_content'];
                            $dmoText = str_replace($masterdmoAttributs->toArray(), $getContentData, $dmoContent);

                            if($requestData->dmo_vdo_type == 0){
                                $dmoBelowPlanName = "Equal to reference price";
                            }else{
                                $dmoBelowPlanName = "Equal to the Victorian Default Offer";
                            }

                            $dmoArray[$data['id']][$rate['distributor_id']]['status']=1;
                            $dmoArray[$data['id']][$rate['distributor_id']]['message']='DMO-VDO-Content found successfully';
                            $dmoArray[$data['id']][$rate['distributor_id']]['tariff_name']='single';
                            $dmoArray[$data['id']][$rate['distributor_id']]['dmo_text']=$dmoText;
                            $dmoArray[$data['id']][$rate['distributor_id']]['dmo_plan_name']=$dmoPlanName;
                            $dmoArray[$data['id']][$rate['distributor_id']]['dmo_below_plan_name']=$dmoBelowPlanName;
                       }
                      
                    } 
                }
            }
            
                    return $dmoArray;
        }

   static function calculateDmoPrice($planRate,$billData,$dmoprices,$meterType,$request){

        $totalUsageDiscount = 0;
        $totalGurrentedUsageDiscount = 0;
        $totalGurrentedSupplyDiscount = 0;
        $totalSupplyDiscount = 0;
        $totalDiscountVal = 0;
        $creditBounus = 0;
        $totalUsageDiscount = 0;
        $recurringMerterCharges = 0;
        $totalUsage = 0;
        $tempUnit = 0;
        $finalData= [];
        $dailyTempUnit = 0;

        $offTempUnit = 0;
        $off_daily_temp_unit = 0;

        $shoulderTempUnit = 0;
        $shoulderDailyTempUnit = 0;

        $c1_temp_unit = 0;
        $c1_daily_temp_unit = 0;

        $c2_temp_unit = 0;
        $c2_daily_temp_unit = 0;

        $peak_flag = true;
        $offpeak_flag = true;
        $shoulder_flag = true;
        $c1_flag = true;
        $usageCountRow = 0;
        $supplyCountRow = 0;
        $isPayOnTime = 0;
        $isDirectDebitDiscount = 0;
        $supplyArr=0;

        $peakData = [];
        if(isset($billData)){
            $billData->toArray();
        }else{
            $billData=[];
        }
        if(isset($billData['peak_usage']) && $billData['peak_usage'] !=""){
				
            $peakData['peak_usage']             = $billData['peak_usage'];
            $peakData['off_peak_usage']         = $billData['off_peak_usage'];
            $peakData['shoulder_usage']         = $billData['shoulder_usage'];
            $peakData['control_load_one_usage'] = $billData['control_load_one_usage'];
            $peakData['control_load_two_usage'] = $billData['control_load_two_usage'];

        }
        
        $rates = array_column($dmoprices->toArray(), 'tariff_type');
       
        $tariffType = array_search($meterType, $rates);
       
        $dmoFilterrate = self::getDMOPriceData($dmoprices[$tariffType], $peakData);
       
       
        $discountArr['usage_discount']['pay_day_discount_usage'] =  $planRate["pay_day_discount_usage"];
        $discountArr['usage_discount']['pay_day_discount_usage_desc'] =  $planRate["pay_day_discount_usage_desc"];

        $discountArr['usage_discount']['direct_debit_discount_usage'] =  $planRate["direct_debit_discount_usage"];
        $discountArr['gurrented_discount_usage'] =  $planRate["gurrented_discount_usage"];


        $discountArr['gurrented_discount_usage_desc'] =  $planRate["gurrented_discount_usage_desc"];

        
        $discountArr['gurrented_discount_supply'] =  $planRate["gurrented_discount_supply"];
        $discountArr['gurrented_discount_supply_desc'] =  $planRate["gurrented_discount_supply_desc"];

        $discountArr['supply_discount']['pay_day_discount_supply'] =  $planRate["pay_day_discount_supply"];
        $discountArr['supply_discount']['pay_day_discount_supply_desc'] =  $planRate["pay_day_discount_supply_desc"];


        $discountArr['supply_discount']['direct_debit_discount_supply'] =  $planRate["direct_debit_discount_supply"];

        $discountArr['usage_discount']['direct_debit_discount_desc'] =  $planRate["direct_debit_discount_desc"];
        $discountArr['supply_discount']['direct_debit_discount_desc'] =  $planRate["direct_debit_discount_desc"];
        

        $discountArr['total_gst_discount_val'] =  $planRate["gst_rate"];


       

        foreach ($discountArr as $key => $value) {

            if($key=="gurrented_discount_usage"){

                if($value!=""){
                    $totalGurrentedUsageDiscount += rtrim($value,"%");
                }

            }else if($key=="gurrented_discount_supply" ){

                if($value!=""){
                    $totalGurrentedSupplyDiscount += rtrim($value,"%");
                }	
            }
        }
        foreach ($discountArr["usage_discount"] as $key => $value) {

            if($key=="pay_day_discount_usage" || $key == "direct_debit_discount_usage"){
                if($value!=""){

                    if($key=="pay_day_discount_usage"){
                        $isPayOnTime = 1;
                    }else if($key == "direct_debit_discount_usage"){
                        $isDirectDebitDiscount =1;
                    }

                    $usageCountRow ++;
                    $totalUsageDiscount += rtrim($value,"%");
                }
            }
        }
      
        foreach ($discountArr["supply_discount"] as $key => $value) {
	 	 		
            if($key=="pay_day_discount_supply" || $key == "direct_debit_discount_supply"){
                if($value!=""){

                    if($key=="pay_day_discount_supply"){
                        $isPayOnTime = 1;
                    }else if($key == "direct_debit_discount_supply"){
                        $isDirectDebitDiscount =1;
                    }

                    $usageCountRow ++;
                    $totalSupplyDiscount += rtrim($value,"%");
                }
            }	
        }
        $peakUnits = isset($dmoFilterrate['peak'])?$dmoFilterrate['peak']:'';
        $offpeakUnits = isset($dmoFilterrate['off_peak'])?$dmoFilterrate['off_peak']:'';
        $shoulderUnits = isset($dmoFilterrate['shoulder'])?$dmoFilterrate['shoulder']:'';
        $c1Units = isset($dmoFilterrate['c1'])?$dmoFilterrate['c1']:'';
        $c2Units = isset($dmoFilterrate['c2'])?$dmoFilterrate['c2']:'';
        $tariffName= $dmoprices[$tariffType]["tariff_name"];

      
      
        $planRateLimit=$planRate['plan_rate_limit'];

        foreach ($planRateLimit as $key => $value) {
            $total= 0;

            $total_peak_cal = [];
            // if(in_array($value['limit_type'], $delete_arr)){
            //     unset($planRateLimit[$key]);
            // }else{

                if($value['limit_type']=="peak" && $peakUnits!=""){

                        $check_daily_usage = ($peakUnits/365);//daily usage according to total usage found.
                        $check_daily_usage -= $tempUnit;

                          if ($value['limit_daily'] != "" && $value['limit_daily'] != 0 && $peakUnits > $value['limit_daily']) {
                              $usage_limit_daily = $value['limit_daily'];
                              
                          } elseif ($value['limit_year'] != "" && $value['limit_year'] != 0 && $peakUnits > $value['limit_year']) {
                              $usage_limit_daily = $value['limit_year'] / 365;
                              
                          } else {
                              $usage_limit_daily = 0;
                              
                          }

                          if($check_daily_usage <= $usage_limit_daily){
                              $usage_limit_daily = $check_daily_usage;			      
                          }
                          
                            if($peak_flag){

                              if ($usage_limit_daily != "" && $usage_limit_daily != 0 && $peakUnits > $usage_limit_daily) {
                                  $total += $usage_limit_daily * $value['limit_charges'];
                                  $tempUnit += $usage_limit_daily;
                              } else { 
                                  
                                  $daily_temp_unit = (($peakUnits/365)-$tempUnit);
                                  $total += $daily_temp_unit * $value['limit_charges'];
                                  $tempUnit += $usage_limit_daily;
                              }

                              $peak_daily_total_var = $total/100;

                                $total_peak_cal['total_peak'] = $peak_daily_total_var*365;	 						 	 				
                                $merge_arr = array_merge($value,$total_peak_cal);
                                $peakArr[] = $merge_arr;	 	 	

                                $totalUsage += $total_peak_cal['total_peak'];		

                            }	

                            if($check_daily_usage <= $usage_limit_daily){
                              $peak_flag = false;		                    	
                            }
                                        
                }else if($value['limit_type']=="summer_peak" && $peakUnits!=""){

                        $check_daily_usage = ($peakUnits/365);//daily usage according to total usage found.
                        $check_daily_usage -= $tempUnit;

                          if ($value['limit_daily'] != "" && $value['limit_daily'] != 0 && $peakUnits > $value['limit_daily']) {
                              $usage_limit_daily = $value['limit_daily'];
                              
                          } elseif ($value['limit_year'] != "" && $value['limit_year'] != 0 && $peakUnits > $value['limit_year']) {
                              $usage_limit_daily = $value['limit_year'] / 365;
                              
                          } else {
                              $usage_limit_daily = 0;
                              
                          }

                          if($check_daily_usage <= $usage_limit_daily){
                              $usage_limit_daily = $check_daily_usage;			      
                          }
                          
                            if($peak_flag){

                              if ($usage_limit_daily != "" && $usage_limit_daily != 0 && $peakUnits > $usage_limit_daily) {
                                  $total += $usage_limit_daily * $value['limit_charges'];
                                  $tempUnit += $usage_limit_daily;
                              } else { 
                                  
                                  $daily_temp_unit = (($peakUnits/365)-$tempUnit);
                                  $total += $daily_temp_unit * $value['limit_charges'];
                                  $tempUnit += $usage_limit_daily;
                              }

                              $peak_daily_total_var = $total/100;

                                $total_peak_cal['total_peak'] = $peak_daily_total_var*365;	 						 	 				
                                $merge_arr = array_merge($value,$total_peak_cal);
                                $peakArr[] = $merge_arr;	 	 	

                                $totalUsage += $total_peak_cal['total_peak'];		

                            }	

                            if($check_daily_usage <= $usage_limit_daily){
                              $peak_flag = false;		                    	
                            }
                                        
                }else if($value['limit_type']=="offpeak" && $offpeakUnits!=""){


                        $check_daily_usage = ($offpeakUnits/365);//daily usage according to total usage found.
                        $check_daily_usage -= $off_daily_temp_unit;

                      if ($value['limit_daily'] != "" && $value['limit_daily'] != 0 && $offpeakUnits > $value['limit_daily']) {
                          $usage_limit_daily = $value['limit_daily'];
                      } elseif ($value['limit_year'] != "" && $value['limit_year'] != 0 && $offpeakUnits > $value['limit_year']) {
                          $usage_limit_daily = $value['limit_year'] / 365;
                      } else {
                          $usage_limit_daily = 0;
                      }

                      if($check_daily_usage <= $usage_limit_daily){
                              $usage_limit_daily = $check_daily_usage;			      
                          }

                      if($offpeak_flag){

                          if ($usage_limit_daily != "" && $usage_limit_daily != 0 && $offpeakUnits > $usage_limit_daily) {
                              $total += $usage_limit_daily * $value['limit_charges'];
                              $offTempUnit += $usage_limit_daily;
                          } else { 
                              
                              $off_daily_temp_unit = (($offpeakUnits/365)-$offTempUnit);
                              $total += $off_daily_temp_unit * $value['limit_charges'];
                              $offTempUnit += $usage_limit_daily;
                          }
                          $peak_daily_total_var = $total/100;

                            $total_peak_cal['total_peak'] = $peak_daily_total_var*365;	 						 	 				
                            $merge_arr = array_merge($value,$total_peak_cal);
                            $offpeakArr[] = $merge_arr;	 	 	

                            $totalUsage += $total_peak_cal['total_peak'];	
                        }
                            
                        if($check_daily_usage <= $usage_limit_daily){
                          $offpeak_flag = false;		                    	
                        }


                }else if($value['limit_type'] == "shoulder" && $shoulderUnits!=""){ 

                        $check_daily_usage = ($shoulderUnits/365);//daily usage according to total usage found.
                        $check_daily_usage -= $shoulderTempUnit;

                      if ($value['limit_daily'] != "" && $value['limit_daily'] != 0 && $shoulderUnits > $value['limit_daily']) {
                          $usage_limit_daily = $value['limit_daily'];
                      } elseif ($value['limit_year'] != "" && $value['limit_year'] != 0 && $shoulderUnits > $value['limit_year']) {
                          $usage_limit_daily = $value['limit_year'] / 365;
                      } else {
                          $usage_limit_daily = 0;
                      }

                      if($check_daily_usage <= $usage_limit_daily){
                              $usage_limit_daily = $check_daily_usage;			      
                          }


                      if($shoulder_flag){

                          if ($usage_limit_daily != "" && $usage_limit_daily != 0 && $shoulderUnits > $usage_limit_daily) {
                              $total += $usage_limit_daily * $value['limit_charges'];
                              $shoulderTempUnit += $usage_limit_daily;
                          } else { 
                              
                              $shoulder_daily_temp_unit = (($shoulderUnits/365)-$shoulderTempUnit);
                              $total += $shoulder_daily_temp_unit * $value['limit_charges'];
                              $shoulderTempUnit += $usage_limit_daily;
                          }
                          $peak_daily_total_var = $total/100;

                            $total_peak_cal['total_peak'] = $peak_daily_total_var*365;	 						 	 				
                            $merge_arr = array_merge($value,$total_peak_cal);
                            $shoulderArr[] = $merge_arr;	 
                            $totalUsage += $total_peak_cal['total_peak'];	 	
                        }

                        if($check_daily_usage <= $usage_limit_daily){
                          $shoulder_flag = false;		                    	
                        }

                }else if($value['limit_type']=="c1" && $c1Units!=""){

                        $check_daily_usage = ($c1Units/365);//daily usage according to total usage found.
                        $check_daily_usage -= $c1_temp_unit;
                      if ($value['limit_daily'] != "" && $value['limit_daily'] != 0 && $c1Units > $value['limit_daily']) {
                          $usage_limit_daily = $value['limit_daily'];
                      } elseif ($value['limit_year'] != "" && $value['limit_year'] != 0 && $c1Units > $value['limit_year']) {
                          $usage_limit_daily = $value['limit_year'] / 365;
                      } else {
                          $usage_limit_daily = 0;
                      }

                      if($check_daily_usage <= $usage_limit_daily){
                              $usage_limit_daily = $check_daily_usage;			      
                          }

                      if($c1_flag){

                          if ($usage_limit_daily != "" && $usage_limit_daily != 0 && $c1Units > $usage_limit_daily) {
                              $total += $usage_limit_daily * $value['limit_charges'];
                              $c1_temp_unit += $usage_limit_daily;
                          } else { 
                              
                              $c1_daily_temp_unit = (($c1Units/365)-$c1_temp_unit);
                              $total += $c1_daily_temp_unit * $value['limit_charges'];
                              $c1_temp_unit += $usage_limit_daily;
                          }
                          $peak_daily_total_var = $total/100;

                            $total_peak_cal['total_peak'] = $peak_daily_total_var*365;	 						 	 				
                            $merge_arr = array_merge($value,$total_peak_cal);
                            $c1Arr[] = $merge_arr;	 	 	

                            $totalUsage += $total_peak_cal['total_peak'];
                        }
                            
                        if($check_daily_usage <= $usage_limit_daily){
                          $c1_flag = false;		                    	
                        }


                }else if($value['limit_type']=="c2" && $c2Units!=""){

                        $check_daily_usage = ($c2Units/365);//daily usage according to total usage found.
                        $check_daily_usage -= $c2_temp_unit;
                      if ($value['limit_daily'] != "" && $value['limit_daily'] != 0 && $c2Units > $value['limit_daily']) {
                          $usage_limit_daily = $value['limit_daily'];
                      } elseif ($value['limit_year'] != "" && $value['limit_year'] != 0 && $c2Units > $value['limit_year']) {
                          $usage_limit_daily = $value['limit_year'] / 365;
                      } else {
                          $usage_limit_daily = 0;
                      }

                      if($check_daily_usage <= $usage_limit_daily){
                              $usage_limit_daily = $check_daily_usage;			      
                          }

                      if($c1_flag){

                          if ($usage_limit_daily != "" && $usage_limit_daily != 0 && $c2Units > $usage_limit_daily) {
                              $total += $usage_limit_daily * $value['limit_charges'];
                              $c2_temp_unit += $usage_limit_daily;
                          } else { 
                              
                              $c2_daily_temp_unit = (($c2Units/365)-$c2_temp_unit);
                              $total += $c2_daily_temp_unit * $value['limit_charges'];
                              $c2_temp_unit += $usage_limit_daily;
                          }
                          $peak_daily_total_var = $total/100;

                            $total_peak_cal['total_peak'] = $peak_daily_total_var*365;	 						 	 				
                            $merge_arr = array_merge($value,$total_peak_cal);
                            $c2Arr[] = $merge_arr;	 	 	

                            $totalUsage += $total_peak_cal['total_peak'];
                        }
                       	 	 				

                }

                if(!empty($peakArr)){
                    $dmoFilterrate['peak_arr'] = $peakArr;
                }
                if(!empty($offpeakArr)){	 	 	
                    $dmoFilterrate['off_peak_arr'] = $offpeakArr;
                }	
                if(!empty($shoulderArr)){	 	
                    $dmoFilterrate['shoulder_arr'] = $shoulderArr;
                }
                if(!empty($c1Arr)){	 	 	
                    $dmoFilterrate['c1_arr'] = $c1Arr;
                }	
                if(!empty($c2Arr)){	 	 	
                    $dmoFilterrate['c2_arr'] = $c2Arr;
                }

                $dmoFilterrate['discount_arr'] = $discountArr;
                $dmoFilterrate['total_usage'] = $totalUsage;
                $totalSupplyChargesCal = 0;
                if($planRate['control_load_1_daily_supply_charges'] !=0 && $planRate['control_load_2_daily_supply_charges'] != 0){

                    $supplyArr[1]['daily_supply_charges'] = $planRate['control_load_1_daily_supply_charges'];
                    $supplyArr[1]['supply_charges_cal'] = ($planRate['control_load_1_daily_supply_charges']/100)*365;
                    
                    $supplyArr[2]['daily_supply_charges'] = $planRate['control_load_2_daily_supply_charges'];
                    $supplyArr[2]['supply_charges_cal'] = ($planRate['control_load_2_daily_supply_charges']/100)*365;
                    $totalSupplyChargesCal += $supplyArr[1]['supply_charges_cal'] + $supplyArr[2]['supply_charges_cal'];
  
                }else if($planRate['control_load_1_daily_supply_charges'] !=0 && $planRate['control_load_2_daily_supply_charges'] == 0){
  
                    $supplyArr[1]['daily_supply_charges'] = $planRate['control_load_1_daily_supply_charges'];
                    $supplyArr[1]['supply_charges_cal'] = ($planRate['control_load_1_daily_supply_charges']/100)*365;
  
                    $totalSupplyChargesCal += $supplyArr[1]['supply_charges_cal'];	 	 		 	 		
  
                }else if($planRate['control_load_1_daily_supply_charges'] == 0 && $planRate['control_load_2_daily_supply_charges'] != 0){
  
                    $supplyArr[2]['daily_supply_charges'] = $planRate['control_load_2_daily_supply_charges'];
                    $supplyArr[2]['supply_charges_cal'] = ($planRate['control_load_2_daily_supply_charges']/100)*365;
                    
                    $totalSupplyChargesCal += $supplyArr[2]['supply_charges_cal'];	 	 	 		 	 		
  
                }

                $dmoFilterrate['supply_charges'] = $supplyArr;


                $total_gurrented_discount_on_usage = ($totalUsage*$totalGurrentedUsageDiscount)/100;	 	 	
	 	 	$total_gurrented_discount_on_supply = ($totalSupplyChargesCal*$totalGurrentedSupplyDiscount)/100;
 
 			// Total Gurrented Discount on Usage And Supply
	 	 	$dmoFilterrate["total_gurrented_discount_on_usage"] = $total_gurrented_discount_on_usage;
	 	 	$dmoFilterrate["total_gurrented_discount_on_supply"] = $total_gurrented_discount_on_supply;

	 	 	//After Gurrented Discount Supply and Usage
	 	 	$dmoFilterrate["total_usage_after_gurrented_discount"] = $totalUsage - $total_gurrented_discount_on_usage;
	 	 	$dmoFilterrate["total_supply_after_gurrented_discount"] = $totalSupplyChargesCal - $total_gurrented_discount_on_supply;

			/*for dmo static content manage backend*/
			$dmoFilterrate["front_total_gurrented_discount_on_supply"] = round($dmoFilterrate['total_gurrented_discount_on_supply'],2);
			$dmoFilterrate["front_total_gurrented_discount_on_usage"] = round($dmoFilterrate['total_gurrented_discount_on_usage'],2);
			/**************/

	 	 	//Total Usage Discount with Gurrented on Supply and Usage 
	 	 	$dmoFilterrate["total_usage_discount"] = ($dmoFilterrate["total_usage_after_gurrented_discount"]*$totalUsageDiscount)/100;
	 	 	$dmoFilterrate["total_supply_discount"] = ($dmoFilterrate["total_supply_after_gurrented_discount"]*$totalSupplyDiscount)/100;


	 	 	$dmoFilterrate["total_usage_with_gurrented_and_other_discount"] = ($dmoFilterrate["total_usage_after_gurrented_discount"]*$totalUsageDiscount)/100;

	 	 	$dmoFilterrate["total_supply_with_gurrented_and_other_discount"] = ($dmoFilterrate["total_supply_after_gurrented_discount"]*$totalSupplyDiscount)/100;

		 	$total_calculation_val = $dmoFilterrate["total_usage_after_gurrented_discount"] +  $dmoFilterrate["total_supply_after_gurrented_discount"];

		 	$total_discount_on_usage_supply = $dmoFilterrate["total_usage_discount"] + $dmoFilterrate["total_supply_discount"];
			
			/*for dmo static content manage backend*/
			$dmoFilterrate["front_total_supply_discount"] = round($dmoFilterrate['total_supply_discount'],2);
			$dmoFilterrate["front_total_usage_discount"] =round($dmoFilterrate['total_usage_discount'],2);
			/**************/

		 	$dmoFilterrate['total_calculation_val'] = $total_calculation_val;

		 	//$credit_recurring =  $credit_bounus - $recurring_merter_charges;
		 	
		 	$dmoFilterrate['total_calculation_after_discount'] = ($total_calculation_val - $total_discount_on_usage_supply) + $recurringMerterCharges;
		 	

		 	$dmoFilterrate['total_calculation_after_discount'] = $dmoFilterrate['total_calculation_after_discount'] - $creditBounus;
		 	$dmoFilterrate['front_total_calculation_after_discount'] = round($dmoFilterrate['total_calculation_after_discount'],2);

            $dmoFilterrate['total_calculation_after_gst'] =  "";
		 	$dmoFilterrate['static_without_conditional_low_price_cap_percentage'] ="";
		 	$dmoFilterrate['static_low_price_cap_percentage'] ="";
		 	$dmoFilterrate['dmo_static_content_status'] = "";

             if($discountArr['total_gst_discount_val'] !=""){

                $dmoFilterrate['total_gst_discount'] = ($dmoFilterrate['total_calculation_after_discount']*$discountArr['total_gst_discount_val'])/100;

                $dmoFilterrate['total_calculation_after_gst'] =  $dmoFilterrate['total_calculation_after_discount'] + $dmoFilterrate['total_gst_discount'];

                $dmoFilterrate['front_total_gst_discount'] = round($dmoFilterrate['total_gst_discount'],2);

                $dmoFilterrate['front_total_calculation_after_gst'] =round($dmoFilterrate['total_calculation_after_gst'],2);


            }

            $percentageVar = "";
		 	$dmoFilterrate["supply_count_row"] = $supplyCountRow;
		 	$dmoFilterrate["usage_count_row"] = $usageCountRow;

		 	$dmoFilterrate['credit_bounus'] = $creditBounus;

		 	$dmoFilterrate['recurring_merter_charges'] = $recurringMerterCharges;
		 	
			$dmoFilterrate['total_after_recurring_meter'] = $dmoFilterrate['total_calculation_after_gst'] ;
			
			$dmoFilterrate['total_after_credit_bounus'] = $dmoFilterrate['total_after_recurring_meter'] ;

			$dmoFilterrate['price_cap_percentage'] =  ($dmoFilterrate['total_after_credit_bounus']/$dmoFilterrate['annual_price'])*100;

			$dmoFilterrate['front_price_cap_percentage'] = round($dmoFilterrate['price_cap_percentage'],3);

		  	$price_value = abs(round($dmoFilterrate['price_cap_percentage'],3));
		  	$whole_price_value = floor($price_value);
		  	$diff_price_value  = round($price_value - $whole_price_value ,3);
		  	if(0.001<= $diff_price_value){
		  		$new_price_cap_percentage = $whole_price_value+1;
		  	}else{
		  		$new_price_cap_percentage = $whole_price_value;
		  	}

            $dmoFilterrate['low_price_cap_percentage'] =  100 - $new_price_cap_percentage;			
			//$dmoFilterrate['low_price_cap_percentage'] =  100 - $dmoFilterrate['price_cap_percentage'];

			$dmoFilterrate['price_cap_without_conditional_percentage'] =  $dmoFilterrate['total_calculation_after_discount'] + $total_discount_on_usage_supply ;
			

			$dmoFilterrate['price_cap_without_conditional_percentage_with_gst'] =  ($dmoFilterrate['price_cap_without_conditional_percentage']*$discountArr['total_gst_discount_val'])/100;

			
			$dmoFilterrate['price_cap_without_conditional_percentage'] = $dmoFilterrate['price_cap_without_conditional_percentage'] + $dmoFilterrate['price_cap_without_conditional_percentage_with_gst'];

			$dmoFilterrate['without_price_cap_percentage'] =  ($dmoFilterrate['price_cap_without_conditional_percentage']/$dmoFilterrate['annual_price'])*100;

			$dmoFilterrate['front_without_price_cap_percentage'] = round($dmoFilterrate['without_price_cap_percentage'],3);

		  	$org_value = abs(round($dmoFilterrate['without_price_cap_percentage'],3));
		  	$whole_value = floor($org_value);
		  	$diff_value  = round($org_value - $whole_value ,3);
		  	if(0.001<= $diff_value ){
		  		$new_without_price_cap_percentage = $whole_value + 1;
		  	}else{
		  		$new_without_price_cap_percentage = $whole_value;
		  	}
			
			$dmoFilterrate['without_conditional_low_price_cap_percentage'] = 100 - $new_without_price_cap_percentage;

			if($dmoFilterrate['without_conditional_low_price_cap_percentage']== 0){
                $less_more_text ="equal to";
         	}elseif($dmoFilterrate['without_conditional_low_price_cap_percentage']<0){
                $less_more_text = "more than"; 
	  			$percentage_var = abs(round($dmoFilterrate['without_conditional_low_price_cap_percentage'],0))."% ";
			}else{
	  			$percentage_var = abs(round($dmoFilterrate['without_conditional_low_price_cap_percentage'],0))."% ";
			  	$less_more_text ="less than";

			}
            $dmo_text_usage_supply_discount = ($total_discount_on_usage_supply/$dmoFilterrate["annual_price"])*100;

            $finalData['difference_without_conditional_discount'] =$percentage_var;
            $finalData['difference_with_conditional_discount'] = abs(round($dmo_text_usage_supply_discount,0))."% ";
            $finalData['lowest_possible_annual_cost'] = ceil(round($dmoFilterrate['total_calculation_after_gst'],2));

            if($isDirectDebitDiscount==0 && $isPayOnTime==1){
                $finalData['masterContentType'] = 'with_pay_on_time_discount';
                $finalData['masterContentType'] = 2;

            }else if($isDirectDebitDiscount==1 && $isPayOnTime==0){
                
                $finalData['masterContentType'] = 3;
            }else if($isDirectDebitDiscount==1 && $isPayOnTime==1){
                $finalData['masterContentType'] = 4;
            }else{
                $finalData['masterContentType'] = 1;
            }

            if($dmoFilterrate['low_price_cap_percentage']==0){
                if($request->dmo_vdo_type == 0){
                    $front_low_less_more_text = "Equal to reference price";
                }else{
                    $front_low_less_more_text = "Equal to the Victorian Default Offer";
                }	
            }elseif($dmoFilterrate['low_price_cap_percentage']<0){
                $front_low_less_more_text = "more than";
            }else{
                $front_low_less_more_text = "less than";
            }
            if($dmoFilterrate['low_price_cap_percentage']==0){
                   $dmoFilterrate['front_low_price_cap_percentage'] = $front_low_less_more_text;
            }else{
                  $dmoFilterrate['front_low_price_cap_percentage']= abs(round($dmoFilterrate['low_price_cap_percentage'],0)).'% '.$front_low_less_more_text;
            }

            if($dmoFilterrate['without_conditional_low_price_cap_percentage']== 0){
                if($request->dmo_vdo_type == 0){
                    $front_less_more_text = "Equal to reference price";
                }else{
                    $front_less_more_text = "Equal to the Victorian Default Offer";
                }
            }elseif($dmoFilterrate['without_conditional_low_price_cap_percentage']<0){
                  $front_less_more_text = "more than";
            }else{
                  $front_less_more_text = "less than";
            }
            if($dmoFilterrate['without_conditional_low_price_cap_percentage']== 0){

                $dmoFilterrate['front_without_conditional_low_price_cap_percentage']= $front_less_more_text;
            }else{

                $dmoFilterrate['front_without_conditional_low_price_cap_percentage']= abs(round($dmoFilterrate['without_conditional_low_price_cap_percentage'],0)).'% '.$front_less_more_text;
            }
            $finalData['less_more_text'] =$less_more_text;
            $dmoFilterrate['front_total_after_credit_bonus'] = ceil(round($dmoFilterrate['total_after_credit_bounus'],2));
            /***************/
            if(abs(round($dmoFilterrate['without_conditional_low_price_cap_percentage'],0))<= 0){
                $finalData['dmo_content_plan_name'] = "Equal To";
            }else{
                $finalData['dmo_content_plan_name'] = abs(round($dmoFilterrate['without_conditional_low_price_cap_percentage'],0))."% ".$less_more_text;
            }
            
            //}
        }
        return $finalData;
        
   }



   static function getDMOPriceData($dmorate, $peakData){

    
    $data = [];
    $data['annual_price'] = $dmorate['annual_price'];
    $data['front_annual_price'] =round($data['annual_price'],2);
    $data['annual_usage'] = $dmorate['annual_usage'];
    $data['tariff_type'] = $dmorate['tariff_type'];

    if($dmorate['tariff_type']=="timeofuse_c1_c2"){

        if($peakData['control_load_one_usage'] != "" && $peakData['control_load_two_usage'] !="" && $peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] !="" ){

                $data['peak'] = $dmorate['peak_shoulder_peak']; 	
                $data['off_peak'] = $dmorate['peak_shoulder_offpeak']; 	
                $data['shoulder'] = $dmorate['peak_shoulder_shoulder']; 	
                $data['c1'] = $dmorate['control_load_1_2_1']; 	
                $data['c2'] = $dmorate['control_load_1_2_2']; 	
        }

        if($peakData['control_load_one_usage'] != "" && $peakData['control_load_two_usage'] !="" && $peakData['off_peak_usage'] =="" && $peakData['shoulder_usage'] !="" ){

                $data['peak'] = $dmorate['peak_shoulder_1_2_peak']; 	
                $data['off_peak'] = ''; 	
                $data['shoulder'] = $dmorate['peak_shoulder_1_2_shoulder_1']; 	
                $data['c1'] = $dmorate['control_load_1_2_1']; 	
                $data['c2'] = $dmorate['control_load_1_2_2']; 	 			

        }
        if($peakData['control_load_one_usage'] != "" && $peakData['control_load_two_usage'] !="" && $peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] =="" ){

                $data['peak'] = $dmorate['peak_offpeak_peak']; 	
                $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
                $data['shoulder'] = ''; 	
                $data['c1'] = $dmorate['control_load_1_2_1']; 	
                $data['c2'] = $dmorate['control_load_1_2_2']; 	    			

        }

        if($peakData['control_load_one_usage'] != "" && $peakData['control_load_two_usage'] !="" && $peakData['off_peak_usage'] =="" && $peakData['shoulder_usage'] =="" ){

                $data['peak'] = $dmorate['peak_only']; 	
                $data['off_peak'] = ''; 	
                $data['shoulder'] = ''; 	
                $data['c1'] = $dmorate['control_load_1_2_1']; 	
                $data['c2'] = $dmorate['control_load_1_2_2']; 	     			

        }


    }else if($dmorate['tariff_type']=="timeofuse_c1"){

        if($peakData['control_load_one_usage'] != "" && $peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] !="" ){

                $data['peak'] = $dmorate['peak_shoulder_peak']; 	
                $data['off_peak'] = $dmorate['peak_shoulder_offpeak']; 	
                $data['shoulder'] = $dmorate['peak_shoulder_shoulder']; 	
                $data['c1'] = $dmorate['control_load_1']; 		
                $data['c2'] = ""; 		
        }

        if($peakData['control_load_one_usage'] != "" && $peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] =="" ){

                $data['peak'] = $dmorate['peak_offpeak_peak']; 	
                $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
                $data['shoulder'] = ""; 	
                $data['c1'] = $dmorate['control_load_1']; 		
                $data['c2'] = ""; 
        }

        if($peakData['control_load_one_usage'] != "" && $peakData['off_peak_usage'] =="" && $peakData['shoulder_usage'] !="" ){

                $data['peak'] = $dmorate['peak_shoulder_1_2_peak']; 	
                $data['off_peak'] = ""; 	
                $data['shoulder'] = $data["peak_shoulder_1_2_shoulder_1"]; 	
                $data['c1'] = $dmorate['control_load_1']; 	
                $data['c2'] = ""; 	    				
        }    		
        if($peakData['control_load_one_usage'] != "" && $peakData['off_peak_usage'] =="" && $peakData['shoulder_usage'] =="" ){

                $data['peak'] = $dmorate['peak_only']; 	
                $data['off_peak'] = ""; 	
                $data['shoulder'] = ""; 	
                $data['c1'] = $dmorate['control_load_1']; 	
                $data['c2'] = ""; 	    				
        }

    }else if($dmorate['tariff_type']=="timeofuse_c2"){

        if($peakData['control_load_two_usage'] != "" && $peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] !="" ){

                $data['peak'] = $dmorate['peak_shoulder_peak']; 	
                $data['off_peak'] = $dmorate['peak_shoulder_offpeak']; 	
                $data['shoulder'] = $dmorate['peak_shoulder_shoulder']; 
                $data['c1'] = ""; 	    				
                $data['c2'] = $dmorate['control_load_2']; 		
        }

        if($peakData['control_load_two_usage'] != "" && $peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] =="" ){

                $data['peak'] = $dmorate['peak_offpeak_peak']; 	
                $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
                $data['shoulder'] = ""; 	
                $data['c1'] = ""; 
                $data['c2'] = $dmorate['control_load_2']; 		
        }

        if($peakData['control_load_two_usage'] != "" && $peakData['off_peak_usage'] =="" && $peakData['shoulder_usage'] !="" ){

                $data['peak'] = $dmorate['peak_shoulder_1_2_peak']; 	
                $data['off_peak'] = ""; 	
                $data['shoulder'] = $data["peak_shoulder_1_2_shoulder_1"]; 	
                $data['c1'] = ""; 
                $data['c2'] = $dmorate['control_load_2']; 		
        }    		
        if($peakData['control_load_two_usage'] != "" && $peakData['off_peak_usage'] =="" && $peakData['shoulder_usage'] =="" ){

                $data['peak'] = $dmorate['peak_only']; 	
                $data['off_peak'] = ""; 	
                $data['shoulder'] = ""; 
                $data['c1'] = ""; 	
                $data['c2'] = $dmorate['control_load_2']; 		
        }


    }else if($dmorate['tariff_type']=="timeofuse_only"){


        if($peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] !="" ){

                $data['peak'] = $dmorate['peak_shoulder_peak']; 	
                $data['off_peak'] = $dmorate['peak_shoulder_offpeak']; 	
                $data['shoulder'] = $dmorate['peak_shoulder_shoulder']; 
                $data['c1'] = ""; 	    				
                $data['c2'] = ""; 		
        }

        if($peakData['off_peak_usage'] !="" && $peakData['shoulder_usage'] =="" ){

                $data['peak'] = $dmorate['peak_offpeak_peak']; 	
                $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
                $data['shoulder'] = ""; 
                $data['c1'] = ""; 	    				
                $data['c2'] = ""; 		
        }
    //Conditions added by komal on 22nd june 2019
    }else if($dmorate['tariff_type']=="peak_only"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    				
        $data['c2'] = ""; 		

    }else if($dmorate['tariff_type']=="peak_c1"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1']; 	    		
        $data['c2'] = "";
    }else if($dmorate['tariff_type']=="peak_c2"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    				
        $data['c2'] = $dmorate['control_load_2'];
    }else if($dmorate['tariff_type']=="peak_c1_c2"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1_2_1']; 	    		
        $data['c2'] = $dmorate['control_load_1_2_2'];
    //Conditions added by komal on 24th june 2019
    }else if($dmorate['tariff_type']=="summer_only"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    				
        $data['c2'] = "";
    }else if($dmorate['tariff_type']=="summer_c1"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1']; 	    			
        $data['c2'] = "";
    }else if($dmorate['tariff_type']=="summer_c2"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    			
        $data['c2'] = $dmorate['control_load_2'];
    }else if($dmorate['tariff_type']=="summer_c1_c2"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1_2_1']; 	    	
        $data['c2'] = $dmorate['control_load_1_2_2'];
    }else if($dmorate['tariff_type']=="winter_only"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    	
        $data['c2'] = "";
    }else if($dmorate['tariff_type']=="winter_c1"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1']; 	    	
        $data['c2'] = "";
    }else if($dmorate['tariff_type']=="winter_c2"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    	
        $data['c2'] = $dmorate['control_load_2'];
    }else if($dmorate['tariff_type']=="winter_c1_c2"){

        $data['peak'] = $dmorate['peak_only']; 	
        $data['off_peak'] = ""; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1_2_1']; 	    	
        $data['c2'] = $dmorate['control_load_1_2_2'];
    }else if($dmorate['tariff_type']=="summer_winter_tou_only"){

        $data['peak'] = $dmorate['peak_offpeak_peak']; 	
        $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    	
        $data['c2'] = "";
    }else if($dmorate['tariff_type']=="summer_winter_tou_c1"){

        $data['peak'] = $dmorate['peak_offpeak_peak']; 	
        $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1']; 	    	
        $data['c2'] = "";
    }else if($dmorate['tariff_type']=="summer_winter_tou_c2"){

        $data['peak'] = $dmorate['peak_offpeak_peak']; 	
        $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
        $data['shoulder'] = ""; 
        $data['c1'] = ""; 	    	
        $data['c1'] = $dmorate['control_load_2'];
    }else if($dmorate['tariff_type']=="summer_winter_tou_c1_c2"){

        $data['peak'] = $dmorate['peak_offpeak_peak']; 	
        $data['off_peak'] = $dmorate['peak_offpeak_offpeak']; 	
        $data['shoulder'] = ""; 
        $data['c1'] = $dmorate['control_load_1_2_1']; 	    	
        $data['c2'] = $dmorate['control_load_1_2_2'];
    }else if($dmorate['tariff_type']=="two_rate_only"){

            $data['peak'] = $dmorate['peak_only'];  
            $data['off_peak'] = "";   
            $data['shoulder'] = ""; 
            $data['c1'] = "";           
            $data['c2'] = "";
    }

    return $data;

}
}
