<?php

namespace App\Traits\Connection;

use Illuminate\Support\Facades\DB;

/**
* Connection methods model.
* Author: Sandeep Bangarh
*/

trait EnergyMethods
{
    static function getFirstData ($conditions, $columns='*') {
        return DB::table('sale_product_energy_connection_details')->select($columns)->where($conditions)->first();
    }

    static function getConnectionDetailByLeadId ($leadId, $columns='*') {
        return DB::table('sale_products_energy')->select($columns)
		->join('sale_product_energy_connection_details', 'sale_products_energy.connection_address_id', '=', 'sale_product_energy_connection_details.id')
        ->join('leads', 'sale_products_energy.lead_id', '=', 'leads.lead_id')
        ->join('lead_journey_data_energy', 'leads.lead_id', '=', 'lead_journey_data_energy.lead_id')
        ->join('visitors', 'leads.visitor_id', '=', 'visitors.id')->where('leads.lead_id', $leadId)
        ->first();
    }
}