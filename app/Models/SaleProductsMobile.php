<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Plan\ApplyNow;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Product\ { Methods, Query, Relationships, Redis };

class SaleProductsMobile extends Model
{
    use SoftDeletes, ApplyNow, Methods, Query, Relationships, Redis;
    
    protected $table = 'sale_products_mobile';
	protected $fillable=['lead_id','service_id','product_type','provider_id','plan_id','cost_type','cost','reference_no','is_moving','moving_at','sale_created_at','is_duplicate','variant_id','handset_id','own_or_lease','color_id','contract_id'];

    static public function saveMobileSaleProductsData($request, $visitor){
        $leadId = $request->visit_id;
        $plan_data = \DB::table('plans_mobile')->where('id',$request->input('plan_id'))->where('status',1)->select('provider_id','plan_type','contract_id','special_offer_status','special_offer_cost')->first();
        if(empty($plan_data)){
            $status = false;
            $message = "Plan is not available.";
            return ['status' => $status, 'message' => $message];
        }
        $visit_data = \DB::table('lead_journey_data_mobile')->where('lead_id', $leadId)->exists();
        if(empty($visit_data)){
            $status = false;
            $message = "Visit does not exist.";
            return ['status' => $status,  'message' => $message];
        }
        $planData=[];    
        $planData['lead_id'] = $leadId;
        $planData['service_id'] = $request->header('serviceid');
        $planData['product_type'] = $plan_data->plan_type;
        $planData['provider_id'] = $plan_data->provider_id;
        $planData['plan_id'] = $request->input('plan_id');
        $planData['cost_type'] = $request->input('cost_type');
        $planData['cost'] = $plan_data->special_offer_status?$plan_data->special_offer_cost:$request->input('cost');

        $planData['variant_id'] = $request->variant_id;
        $planData['handset_id'] = $request->handset_id;
        $planData['contract_id'] = $plan_data->contract_id;
        $planData['own_or_lease'] = $request->own_or_lease;
        $planData['color_id'] = $request->color_id;

        $isDuplicate = Lead::checkDuplicateLead($visitor, 'mobile');
        $planData['is_duplicate'] = $isDuplicate?1:0;
        
        $result = SaleProductsMobile::updateOrCreate(['lead_id' => $planData['lead_id']], $planData);
        if ($result) {
            $status = true;
            $message = 'Plan Applied Successfully';
        } else {
            $status = false;
            $message = 'Failed';
        }            
        
        return ['status' => $status,  'message' => $message];
    }
    /**
     * Update sim type.
     */
    static function updateData($conditions, $data)
    {
        $isUpdated = self::where($conditions)->update($data);
        return $isUpdated;
    }
    
}
