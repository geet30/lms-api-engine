<?php

namespace App\Traits\Product;
use Illuminate\Support\Facades\DB;

/**
* Broadband Methods model.
* Author: Sandeep Bangarh
*/

trait BroadbandMethods
{
    static function getAddons ($productIds) {
        $addons = DB::table('sale_products_broadband_addon')->where('sale_product_id', $productId)->get();
        foreach($addons as $addon) {
            echo "<pre>";print_r($addon);
        }
        exit;
        $query = DB::table('sale_products_broadband_addon')
			    ->join('visitors', 'leads.visitor_id', '=', 'visitors.id');
    }
}