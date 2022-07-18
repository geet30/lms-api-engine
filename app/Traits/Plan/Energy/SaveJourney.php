<?php

namespace App\Traits\Plan\Energy;

use Illuminate\Http\Request;
use App\Models\Energy\{EnergyLeadJourney, EnergyBillDetails};
use DateTime;
use App\Models\BroadbandLeadJourney;

trait SaveJourney
{
    //function to save journey data
    public static function SaveJourneyData(Request $request)
    {
        if ($request->filter == true) {

            self::saveFilters($request);
        }
        //electricity values
        if ($request->energy_type == "electricitygas") {
            $response['elec_journey_data'] = self::saveElecJourneyValues($request);
            $response['gas_journey_data'] = self::saveGasJourneyValues($request);

            //save bill details
            if ($response['elec_journey_data']['bill_available'] == 1) { 
                $response['elec_bill_data'] = self::saveElecBillValues($request);
            }
            if ($response['gas_journey_data']['bill_available'] == 1) {
                $response['gas_bill_data'] = self::saveGasBillValues($request);
            }
            return   $response;
        } elseif ($request->energy_type == "electricity") {
            $response['elec_journey_data'] = self::saveElecJourneyValues($request);
            if ($response['elec_journey_data']['bill_available'] == 1) {
                $response['elec_bill_data'] = self::saveElecBillValues($request);
            }
            return   $response;
        } elseif ($request->energy_type == "gas") {
            //gasValues
            $response['gas_journey_data'] = self::saveGasJourneyValues($request);
            if ($response['gas_journey_data'] = ['bill_available'] == 1) {
                $response['gas_bill_data'] = self::saveGasBillValues($request);
            }
            return   $response;
        }
    }
    //common function save electricity journey values
    public static function saveElecJourneyValues($request)
    {

        $electricityArr['lead_id']
        = decryptGdprData($request->visit_id);

        if ($request->energy_type == "electricity" && $request->has('action') &&  $request->has('action') == 'update') {
            EnergyLeadJourney::where('lead_id',$electricityArr['lead_id'])->where('energy_type',2)->delete();
            $electricityArr['is_dual'] = 0;
        }
        $electricityArr = [];
        $electricityArr['moving_house'] = $electricityArr['life_support']
            = $electricityArr['solar_panel'] = 0;
        $electricityArr['is_dual'] = 0;
        if ($request->energy_type == "electricitygas") {
            $electricityArr['is_dual'] = 1;
        }

        $electricityArr['property_type']
            = $request->property_type;
       
        if ($request->has('solar_panel')) {

            $electricityArr['solar_panel'] = $request->solar_panel;
            $electricityArr['solar_options']
                = $request->solar_options ? $request->solar_options : 0;
        }
        if ($request->has('life_support')) {
            $electricityArr['life_support'] = $request->life_support;
            $electricityArr['life_support_energy_type']
                = $request->life_support_energy_type;
            $electricityArr['life_support_value']
                = $request->life_support_value;
        }
        if ($request->moving_house == 1) {
            $electricityArr['moving_house'] = 1;
            $electricityArr['moving_date']
                = DateTime::createFromFormat('d/m/Y', $request->moving_date)->format('Y-m-d');
        }
        $electricityArr['distributor_id']
            = $request->elec_distributor_id;
        $electricityArr['previous_provider_id'] = $request->electricity_provider ? $request->electricity_provider :  NULL;
        $electricityArr['elec_concession_rebate_ans']
            =
            $request->elec_concession_rebate_ans ? $request->elec_concession_rebate_ans : 0;
        $electricityArr['elec_concession_rebate_amount']
            =
            $request->elec_concession_rebate_amount ? $request->elec_concession_rebate_amount : 0;
        $electricityArr['gas_concession_rebate_ans']
            =
            $request->gas_concession_rebate_ans ? $request->gas_concession_rebate_ans : 0;
        $electricityArr['gas_concession_rebate_amount']
            =
            $request->gas_concession_rebate_amount ? $request->gas_concession_rebate_amount : 0;

        $electricityArr['lead_id']
            = decryptGdprData($request->visit_id);
        $electricityArr['bill_available'] = 0;
        if ($request->has('electricity_bill') && $request->electricity_bill == 1) {
            $electricityArr['bill_available'] = 1;
        }
        $electricityArr['credit_score'] = $request->credit_score ? $request->credit_score : 0;
        // if ($request->filter == true) {
        //     $electricityArr['filters'] = $request['filter_selection'];
        // } else {
        //     $electricityArr['filters'] = '';
        // }

        return  EnergyLeadJourney::updateOrCreate(['lead_id' => decryptGdprData($request->visit_id), 'energy_type' => 1], $electricityArr);
    }
    //common function save gas journey values
    public static function saveGasJourneyValues($request)
    {
        $gasArr['lead_id'] = decryptGdprData($request->visit_id);

        if ($request->energy_type == "gas" && $request->has('action') &&  $request->has('action') == 'update') {
            EnergyLeadJourney::where('lead_id',$gasArr['lead_id'])->where('energy_type',1)->delete();
            $electricityArr['is_dual'] = 0;
        }
        $gasArr = [];
        //gasValues
        $gasArr['energy_type'] = 2;
        $gasArr['is_dual'] = 0;
        if ($request->energy_type == "electricitygas") {
            $gasArr['is_dual'] = 1;
        }

        $gasArr['property_type']
            = $request->property_type;
        //defualt values
        $gasArr['moving_house'] = $gasArr['life_support']
            = $gasArr['solar_panel'] = 0;
       

        if ($request->has('life_support')) {
            $gasArr['life_support'] = $request->life_support;
            $gasArr['life_support_energy_type']
                = $request->life_support_energy_type;
            $gasArr['life_support_value']
                = $request->life_support_value;
        }
        if ($request->moving_house == 1) {
            $gasArr['moving_house'] = 1;
            $gasArr['moving_date']
                = DateTime::createFromFormat('d/m/Y', $request->moving_date)->format('Y-m-d');
        }
        $gasArr['distributor_id']
            = $request->gas_distributor_id;
        $gasArr['previous_provider_id'] = $request->gas_provider ? $request->gas_provider : NULL;

        $gasArr['elec_concession_rebate_ans']
            =
            $request->elec_concession_rebate_ans ? $request->elec_concession_rebate_ans : 0;
        $gasArr['elec_concession_rebate_amount']
            =
            $request->elec_concession_rebate_amount ? $request->elec_concession_rebate_amount : 0;
        $gasArr['gas_concession_rebate_ans']
            =
            $request->gas_concession_rebate_ans ? $request->gas_concession_rebate_ans : 0;
        $gasArr['gas_concession_rebate_amount']
            =
            $request->gas_concession_rebate_amount ? $request->gas_concession_rebate_amount : 0;

        $gasArr['lead_id']
            = decryptGdprData($request->visit_id);
        $gasArr['bill_available'] = 0;
        if ($request->has('gas_bill') && $request->gas_bill == 1) {
            $gasArr['bill_available'] = 1;
        }
        $gasArr['credit_score'] = $request->credit_score ? $request->credit_score : 0;
        // if ($request->filter == true) {
        //     $gasArr['filters'] = $request['filter_selection'];
        // } else {
        //     $gasArr['filters'] = '';
        // }
        //save journey data
        return EnergyLeadJourney::updateOrCreate(['lead_id' => decryptGdprData($request->visit_id), 'energy_type' => 2], $gasArr);
    }
    //common function control load etc values according to meter_type
    public static function billValuesAccToTariffType(Request $request)
    {
        $elecBillArr['meter_type'] = $request->meter_type;
        $elecBillArr['tariff_type'] = setMeterType($request);
        $elecBillArr['peak_usage'] = $request->electricity_peak_usage;
        if ($request->meter_type == "timeofuse") {
            $elecBillArr['off_peak_usage'] = $request->electricity_off_peak_usage ? $request->electricity_off_peak_usage : null;
            $elecBillArr['shoulder_usage'] = $request->shoulder_usage ? $request->shoulder_usage : null;
        }
        $elecBillArr['control_load_timeofuse'] = 0;
        if ($request->meter_type == "double" || $request->meter_type == "timeofuse") {
            $elecBillArr['control_load_one_usage'] = $request->control_load_one_usage ? $request->control_load_one_usage : null;
            $elecBillArr['control_load_two_usage'] = $request->control_load_two_usage ? $request->control_load_two_usage : null;
            if ($request->control_load_timeofuse == 1) {
                $elecBillArr['control_load_timeofuse'] = 1;
                $elecBillArr['control_load_one_off_peak'] = $request->control_load_one_off_peak ? $request->control_load_one_off_peak : null;
                $elecBillArr['control_load_one_shoulder'] =
                    $request->control_load_one_shoulder ? $request->control_load_one_shoulder : null;
                $elecBillArr['control_load_two_off_peak'] =
                    $request->control_load_two_off_peak ? $request->control_load_two_off_peak : null;
                $elecBillArr['control_load_two_shoulder'] =
                    $request->control_load_two_shoulder ? $request->control_load_two_shoulder : null;
            }
        }
        return $elecBillArr;
    }
    //save electricity bill values
    public static function saveElecBillValues($request)
    {
        $elecBillArr['current_provider_id'] = $request->electricity_provider ?  $request->electricity_provider : 0;
        $elecBillArr['usage_level'] = $request->electricity_usage_level;
        // $elecBillArr['credit_score'] = $request->credit_score ? $request->credit_score : 0;
        if ($request->has('solar_panel')) {
            $elecBillArr['solar_usage'] = $request->solar_usage;
            //$elecBillArr['solar_by_back'] = $request->solar_by_back;
            $elecBillArr['solar_tariff'] = $request->solar_tariff;
        }
        //common for gas and electricity
        $elecBillArr['lead_id']  = decryptGdprData($request->visit_id);
        $elecBillArr['bill_start_date'] =
            $elecBillArr['bill_start_date'] =   DateTime::createFromFormat('d/m/Y', $request->electricity_bill_startdate)->format('Y-m-d');

        $elecBillArr['bill_end_date'] =
            DateTime::createFromFormat('d/m/Y', $request->electricity_bill_enddate)->format('Y-m-d');

        $elecBillArr['bill_amount'] = $request->electricity_bill_amount ? $request->electricity_bill_amount : 0;
        $elecBillArr['energy_type'] = 1;
        $demandData = [];
        $billTariffValues = [];
        $demandActive = 1;
        $elecBillArr['solar_usage'] = $elecBillArr['solar_usage'] ? $elecBillArr['solar_usage'] : 0;
        $billTariffValues = Self::billValuesAccToTariffType($request);

        if ($demandActive && $request->has('demand')) {
            if ($request->demand) {
                $demandData = $request->demand_data;
                $demandData['demand_tariff'] = 1;
                if ($demandData['demand_meter_type'] == '') $demandData['demand_meter_type'] = 1;

                $rates = range(1, 4);
                foreach ($rates as $rate) {
                    if ($demandData['demand_rate' . $rate . '_off_peak_usage'] == "") {
                        $demandData['demand_rate' . $rate . '_off_peak_usage'] = 0;
                    }
                    if ($demandData['demand_rate' . $rate . '_peak_usage'] == "") {
                        $demandData['demand_rate' . $rate . '_peak_usage'] = 0;
                    }

                    if ($demandData['demand_rate' . $rate . '_shoulder_usage'] == "") {
                        $demandData['demand_rate' . $rate . '_shoulder_usage'] = 0;
                    }
                    if ($demandData['demand_rate' . $rate . '_days'] == "") {
                        $demandData['demand_rate' . $rate . '_days'] = 0;
                    }
                }
            } else {
                $demandData = ['demand_tariff' => 0];
            }
        }
        $demandWithBill = array_merge($demandData, $elecBillArr);
        $finaleElecBillData = array_merge($demandWithBill, $billTariffValues);

        // if ($request->filter == true) {
        //     $finaleElecBillData['filters'] = $request['filter_selection'];
        // } else {
        //     $finaleElecBillData['filters'] = '';
        // }

        EnergyBillDetails::updateOrCreate(['lead_id' => decryptGdprData($request->visit_id), 'energy_type' => 1], $finaleElecBillData);
    }
    //save gas bill values
    public static function saveGasBillValues($request)
    {
        $gasBillArr['current_provider_id'] = $request->gas_provider ?  $request->gas_provider : 0;
        $gasBillArr['usage_level'] = $request->gas_usage_level;
        $gasBillArr['solar_usage'] = 0;

        // $gasBillArr['credit_score'] = $request->credit_score ? $request->credit_score : 0;
        //common for gas and electricity
        $gasBillArr['lead_id'] = decryptGdprData($request->visit_id);
        $gasBillArr['bill_start_date'] =
            $gasBillArr['bill_start_date'] = DateTime::createFromFormat('d/m/Y', $request->gas_bill_startdate)->format('Y-m-d');

        $gasBillArr['bill_end_date'] =
            DateTime::createFromFormat('d/m/Y', $request->gas_bill_enddate)->format('Y-m-d');

        $gasBillArr['bill_amount'] = $request->gas_bill_amount ? $request->gas_bill_amount : 0;
        $gasBillArr['peak_usage'] =
            $request->gas_peak_usage ? $request->gas_peak_usage : "";
        $gasBillArr['off_peak_usage'] =
            $request->gas_off_peak_usage ? $request->gas_off_peak_usage :null;
        $gasBillArr['energy_type'] = 2;
        $finaleGasBillData = $gasBillArr;


        // if ($request->filter == true) {
        //     $finaleGasBillData['filters'] = $request['filter_selection'];
        // } else {
        //     $finaleGasBillData['filters'] = '';
        // }

        EnergyBillDetails::updateOrCreate(['lead_id' => decryptGdprData($request->visit_id), 'energy_type' => 2], $finaleGasBillData);
    }


    static function saveFilters($request)
    {
        if ($request->filter == true) {
            $filter['filters'] = $request['filter_selection'];
            $filter['lead_id'] = decryptGdprData($request->visit_id);
        } else {
            $filter['filters'] = '';
            $filter['lead_id'] = decryptGdprData($request->visit_id);
        }
        EnergyLeadJourney::where('lead_id', decryptGdprData($request->visit_id))->update($filter);
    }
    //Save broadband journey
    static function saveBroadbandJourneyData($request){
       $data['connection_type'] = $request->connection_type;
       $data['technology_type'] = $request->technology_name;
       $data['movein_type'] = 0;
       $data['movein_date']=null;
       if($request->movin_type == 'yes'){
           $data['movein_type'] = 1;
           $data['movein_date'] = DateTime::createFromFormat('d/m/Y', $request->movin_date)->format('Y-m-d');
       }
       if($request->has('is_agent')){
           $data['source']            = $request->source;
           $data['current_provider']  = $request->current_provider;
           $data['no_of_user']        = $request->no_of_user;
           $data['use_of_internet']   = $request->use_of_internet;
           $data['streaming_type']    = $request->streaming_type;
           $data['spend_crr_bill']    = $request->spend_crr_bill;
       }
       return  BroadbandLeadJourney::updateOrCreate(['lead_id' => $request->visit_id], $data);
    }
}
