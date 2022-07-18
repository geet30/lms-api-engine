<?php

namespace App\Traits\Product;
use DateTime;
use App\Models\Lead;

/**
* Product Methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{

    /**
     * Add Product data for all verticals 
     * @return object request
    */
    static function addProductData ($request) {
        $service = Lead::getService(true);
        $leadId = decryptGdprData($request->visit_id);
        $productType = $request->plan_type??1;
        $productData = [
            'service_id' => $service,
            'provider_id' => $request->electricity_provider??null,
            'is_moving' => $request->moving_house==1?1:null,
            'moving_at' => $request->moving_house==1?(DateTime::createFromFormat('d/m/Y', $request->moving_date)->format('Y-m-d')):null,
            'sale_status' => 1
        ];
        if ($service == 1 && $request->energy_type == "electricitygas") {
            $dataToInsert = [$productData, $productData];
            $dataToInsert[0]['distributor_id'] = $request->elec_distributor_id??null;
            $dataToInsert[1]['provider_id'] = $request->gas_provider??null;
            $dataToInsert[1]['distributor_id'] = $request->gas_distributor_id??null;
            self::updateOrCreate(['lead_id'=>$leadId, 'product_type' => 1], $dataToInsert[0]);
            return self::updateOrCreate(['lead_id'=>$leadId, 'product_type' => 2], $dataToInsert[1]);
        }

        if ($service == 1 && $request->energy_type == "gas") {
            $productData['product_type'] = $productType = 2;
            $productData['provider_id'] = $request->gas_provider??null;
            $productData['distributor_id'] = $request->gas_distributor_id??null;
        }

        if ($service == 1 && $request->energy_type == "electricity") {
            $productData['product_type'] = $productType = 1;
            $productData['distributor_id'] = $request->elec_distributor_id??null;
        }

        return self::updateOrCreate(['lead_id'=>$leadId, 'product_type' => $productType], $productData);
    }
}