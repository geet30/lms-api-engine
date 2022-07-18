<?php

namespace App\Traits\Plan;

use Illuminate\Support\Facades\{DB};

/**
 * Plan Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    static function getPlanBPIDdata($request)
    {
        $distributorIds = $request->distributor_array;
        if (!is_array($distributorIds)) {
            return false;
        }
        
        $query = DB::table('energy_plan_rates');
        if ($request->has('plan_id') && is_array($request->plan_id)) {
            $query = $query->whereIn('plan_id', $request->plan_id);
        }
        return $query->whereIn('distributor_id', $distributorIds)
        ->join('distributors', 'energy_plan_rates.distributor_id', '=', 'distributors.id')
            ->where('energy_plan_rates.is_deleted', 0)
            ->where('energy_plan_rates.status', 1)->select('plan_id', 'name', 'price_fact_sheet', 'tariff_type_title')->get();
    }
}