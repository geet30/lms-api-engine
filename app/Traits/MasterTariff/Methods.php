<?php

namespace App\Traits\MasterTariff;
use Illuminate\Support\Facades\DB;
use App\Models\{Setting};
/**
* MasterTariff methods model.
* Author: Geetanjali Sharma
*/

trait Methods
{
   
    static function getDemandTariff($id =null,$property_type=null)
    {
       	$demandTariffs = self::select('id','tariff_code','tariff_type','master_tariff_ref_id','units_type')
			->where('distributor_id',$id)
			->whereNotNull('master_tariff_ref_id')		
			->where('property_type',$property_type)
			->where('status',1)->get();

			$demandTariff = [];
            $demand_usage_check = Setting::where('key', 'demand_usage_check')->value('value'); 
            if($demand_usage_check) 
                $md_status = true;
            else
                $md_status = false;
            

            foreach($demandTariffs as  $tariffCode){
				if( $tariffCode->units_type == 1)
					$tariff_unit_type = 'KVA';
				else
					$tariff_unit_type = 'KWH';
                $demandTariff['Master_demand'] = $md_status;
				$demandTariff['demand_tariffs'][] = ['id' => $tariffCode->id, 'tariff_code' => $tariffCode->tariff_code, 'tariff_type' => $tariffCode->tariffTypes->tariff_type,'usage_type' => $tariff_unit_type];
			}
            if($demandTariff){
                
				return $demandTariff;
			}  
            return false;		

    }
    
   
}
