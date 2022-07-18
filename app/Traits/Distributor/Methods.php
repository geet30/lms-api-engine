<?php

namespace App\Traits\Distributor;
use Illuminate\Support\Facades\DB;

/**
* Distributor methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{
    /**
     * Validating postcode and getting distributor
     */
    static function getDistributor($request)
    {
        $postCodeArr = explode(',', $request->post_code);

        $energyType = $request->energy_type;

        $distributors = self::getElecAndGasDistributor($postCodeArr[0], $energyType);

        if ($distributors) {
            return $distributors;
        }

        return false;
    }

    /**
     * Getting distributor from database
     */
    static function getElecAndGasDistributor($postCode, $energyType)
    {

        $distributorList = DB::table('distributors')->where('status', '1')->where('is_deleted', '0');

        if ($energyType == 'electricity') {
            $distributorList->where('energy_type', 1);
        }

        if ($energyType == 'gas') {
            $distributorList->where('energy_type', 2);
        }
        $distResult = $distributorList->get()->toArray();
        $listDistributors = array_map(function($obj) { return $obj->id;}, $distResult);
        $distributor = DB::table('distributor_post_codes')->whereIn('distributor_id', $listDistributors)->where('post_code', '=', $postCode)->get();

        if (!$distributor->isEmpty()) {
            $distributor = $distributor->toArray();
            $elecDistributor = [];
            $gasDistributor = [];
            foreach ($distributor as $dist) {
                $neededObject = array_filter(
                    $distResult,
                    function ($obj) use (&$dist) {
                        return $obj->id == $dist->distributor_id;
                    }
                );
                $dist->distributor = (array) end($neededObject);
               
                if ($dist->distributor['energy_type'] == 1) {
                    $elecDistributor[] = (array) $dist;
                } elseif ($dist->distributor['energy_type'] == 2) {
                    $gasDistributor[] = (array) $dist;
                } else {
                    $elecDistributor[] = (array) $dist;
                }
            }
            $distributor = [];
            $distributor['elec_distributor'] = $elecDistributor;
            $distributor['gas_distributor'] = $gasDistributor;
            return (!empty($elecDistributor) || !empty($gasDistributor))?$distributor: false;
        }
        return false;
    }
}
