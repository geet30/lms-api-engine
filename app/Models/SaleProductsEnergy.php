<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Plan\ApplyNow;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Product\ { Methods, Query, Relationships, Redis };

class SaleProductsEnergy extends Model
{
    use SoftDeletes, ApplyNow, Methods, Query, Relationships, Redis;
    
    protected $table = 'sale_products_energy';
	protected $fillable=['lead_id','service_id','product_type','provider_id','plan_id','cost_type','cost','reference_no','is_moving','moving_at','sale_created_at','is_duplicate','dmo_content'];

    static public function removeEnergySaleProductsData($lead_id){
        \DB::table('sale_products_energy')->where('lead_id',$lead_id)->delete();
    }
    static public function saveEnergySaleProductsData($request, $energyType, $visitor){
        $leadId = $request->visit_id;
        $plan_data = \DB::table('plans_energy')->where('id',$request->input('plan_id'))->select('provider_id','energy_type')->first();
        if(empty($plan_data)){
            $status = false;
            $message = "Plan is not available.";
            return ['status' => $status, 'message' => $message];
        }
        $visit_data = \DB::table('lead_journey_data_energy')->where('lead_id', $leadId)->where('energy_type', $energyType)->select('moving_house','moving_date','energy_type')->first();        
        if(empty($visit_data)){
            $status = false;
            $message = "Visit does not exist.";
            return ['status' => $status,  'message' => $message];
        }
        $planData=[];    
        $planData['lead_id'] = $leadId;
        $planData['service_id'] = $request->header('serviceid');
        $planData['product_type'] = $energyType;
        $planData['provider_id'] = $plan_data->provider_id;
        $planData['plan_id'] = $request->input('plan_id');
        $planData['cost_type'] = $request->input('cost_type');
        $planData['cost'] = $request->input('cost');
        $planData['dmo_content'] = $request->input('dmo_content');
        
        $planData['is_moving'] = isset($visit_data->moving_house) ? $visit_data->moving_house : 0;
        if($planData['is_moving'] == 1){
            $planData['moving_at'] = $visit_data->moving_date;
        }

        $isDuplicate = Lead::checkDuplicateLead($visitor, 'energy');
        $planData['is_duplicate'] = $isDuplicate?1:0;


        $result = SaleProductsEnergy::updateOrCreate(['lead_id' => $planData['lead_id'], 'product_type' => $energyType], $planData);
        if ($result) {
            $status = true;
            $message = 'Plan Applied Successfully';
        } else {
            $status = false;
            $message = 'Failed';
        }            
        
        return ['status' => $status, 'message' => $message];
    }
}
