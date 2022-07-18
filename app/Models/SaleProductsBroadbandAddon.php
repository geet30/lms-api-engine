<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Traits\Addon\ { Relationships };


class SaleProductsBroadbandAddon extends Model
{
    use Relationships;

    protected $table = 'sale_products_broadband_addon';
    protected $fillable = ['sale_product_id', 'category_id', 'addon_id', 'addon_type', 'cost', 'cost_type', 'is_mandatory','created_at','updated_at'];
    
    static public function postPlanAddon($addon_array)
    {
        $addon_data = [];
        $exist_data = SaleProductsBroadband::where('id',$addon_array['sale_product_id'])->first();
        if(empty($exist_data)){
            $status = false;
            $message = "Sale product data is not available.";
            return ['status' => $status, 'message' => $message];
        }
        SaleProductsBroadbandAddon::where('sale_product_id',$addon_array['sale_product_id'])->delete();
        $current_date_time = Carbon::now()->toDateTimeString();
        $addon_data = $addon_array['selected_addons'];
        foreach($addon_array['selected_addons'] as $key => $value){
            $addon_data[$key]['sale_product_id'] = $addon_array['sale_product_id'];
            $addon_data[$key]['created_at'] = $current_date_time;
            $addon_data[$key]['updated_at'] = $current_date_time;
        }
        $result = SaleProductsBroadbandAddon::insert($addon_data);
        if ($result) {
            $status = true;
            $message = "Plan Addon save successfully.";
        } else {
            $status = false;
            $message = "Failed.";
        }
        return ['status'=> $status, 'message' => $message];
    }
    static public function deletePlanAddonData($sale_product_id)
    {
        $exist_sale_product_data = SaleProductsBroadband::where('id',$sale_product_id)->first();
        $exist_plan_addon_data = SaleProductsBroadbandAddon::where('id',$sale_product_id)->first();
        if(empty($exist_sale_product_data)){
            $status = false;
            $message = "Sale product data is not available.";
            return ['status' => $status, 'message' => $message];
        }
        if(empty($exist_plan_addon_data)){
            $status = true;
            $message = "Plan Addons data not found.";
            return ['status' => $status, 'message' => $message];
        }
        $result = SaleProductsBroadbandAddon::where('sale_product_id',$sale_product_id)->delete();
        if ($result) {
            $status = true;
            $message = "Plan Addons Data Deleted Successfully.";
        } else {
            $status = false;
            $message = "Failed.";
        }
        return ['status'=> $status, 'message' => $message];
    }
}
