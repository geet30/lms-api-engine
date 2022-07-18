<?php

namespace App\Traits\Plan;
use Illuminate\Support\Facades\DB;
use App\Models\{ Lead, SaleProductsBroadband };

trait ApplyNow
{

    /**
     *
     * Applied plan data save.
     * @param string  planId
     * @return object request
    */
    static public function saveBroadbandSaleProductsData($request, $visitor)
    {
        $leadId = $request->visit_id;
        $provider_id = DB::table('plans_broadbands')->where('id', $request->input('plan_id'))->pluck('provider_id')->first();
        $status = false;
        $message = 'Failed';
        if (empty($provider_id)) {
            $message = "Plan is not available.";
            return ['status' => $status, 'message' => $message];
        }
        $visit_data = DB::table('lead_journey_data_broadband')->where('lead_id', $leadId)->select('movein_type', 'movein_date')->first();
        if (!$visit_data) {
            $message = "Journey data does not exist.";
            return ['status' => $status,  'message' => $message];
        }
        $planData = [];
        $planData['lead_id'] = $leadId;
        $planData['service_id'] = $request->header('serviceid');
        $planData['product_type'] = 0;
        $planData['provider_id'] = $provider_id;
        $planData['plan_id'] = $request->input('plan_id');
        $planData['cost_type'] = $request->input('cost_type');
        $planData['cost'] = $request->input('cost');
        $planData['is_moving'] = isset($visit_data->movein_type) ? $visit_data->movein_type : 0;
        if ($planData['is_moving'] == 1) {
            $planData['moving_at'] = $visit_data->movein_date;
        }

        $isDuplicate = Lead::checkDuplicateLead($visitor, 'broadband');
        $planData['is_duplicate'] = $isDuplicate?1:0;

        $result = SaleProductsBroadband::updateOrCreate(['lead_id' => $planData['lead_id']], $planData);
        if ($result) {
            $status = true;
            $message = 'Plan Applied Successfully';
        }

        return ['status' => $status, 'message' => $message];
    }
    
}
?>