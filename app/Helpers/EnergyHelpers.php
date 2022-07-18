<?php

use App\Models\MoveInCalender;
use Illuminate\Support\Carbon;

if (!function_exists('validateEnergyType')) {

    function validateEnergyType($validator, $value)
    {
        $arr = ['gas', 'electricity', 'electricitygas'];

        if (in_array($value, $arr)) {
            return $validator;
        }

        return $validator->errors()->add('energy_type', 'Energy Type is not correct');
    }
}


if (!function_exists('getDaysDiff')) {
    function getDaysDiff($startDate, $endDate)
    {

        //convert dd/mm/yyyy to yyyy/mm/dd
        $startDate = explode('/', $startDate);

        $startDate = $startDate[2] . '/' . $startDate[1] . '/' . $startDate[0];
        $endDate = explode('/', $endDate);
        $endDate = $endDate[2] . '/' . $endDate[1] . '/' . $endDate[0];


        $date1 = date_create($startDate);
        $date2 = date_create($endDate);
        $diff = date_diff($date1, $date2);
        return ($diff->days + 1);
    }
}
if (!function_exists('getMoveinDays')) {
    function getMoveinDays($request)
    {
        $move_in_days = 0;
        //check if moving house is yes then calculate working days in between
        if ($request['moving_house'] == 1) {
            //get state from full address
            $state = explode(',', $request['post_code']);
            //get all national holidays and selected state holidays also
            $holidays = MoveInCalender::where('holiday_type', 'national')
                ->orWhere(function ($q) use ($state) {
                    $q->where('holiday_type', 'state')
                        ->where('state', trim($state[2]));
                })
                ->pluck('date');

            //calculate days
            $moving_date = explode('/', $request['moving_date']);
            $dt = Carbon::now()->addDay();


            $today = Carbon::now();

            //create a carbon instance of selected moving date

            $dt2 = Carbon::create($moving_date[2], $moving_date[1], $moving_date[0])->addDay();

            $today_diff = $today->diffInDays($dt2, false);

            //get working days according to the selected date.

            $move_in_days = $dt->diffInDaysFiltered(function (Carbon $date) use ($holidays, $today_diff) {
                if (!in_array($date->toDateString(), $holidays->toArray()) && !$date->isWeekend() && $today_diff >= 0)
                    return $date;
            }, $dt2);
        }
        return $move_in_days;
    }
}
if (!function_exists('getPrpertyType')) {
    function getPrpertyType($type){

        if($type == 1)
            return 'Residential';

        if($type == 2)    
            return 'Business';
    }
}
if (!function_exists('setMeterType')) {

    function setMeterType($request)
    {
        $meterType = '';
        if (isset($request['electricity_bill']) && $request['electricity_bill'] == 1) {
            if (isset($request['demand']) && $request['demand'] == 1) {

                if ($request['meter_type'] == 'peak') {
                    $meterType = 'demand_peakonly';
                } elseif ($request['meter_type'] == 'double') {
                    if (isset($request['offpeak_control_load_one_usage']) && $request['offpeak_control_load_one_usage'] != '')
                        $controlLoadOne = true;
                    if (isset($request['offpeak_control_load']) && $request['offpeak_control_load'] == 'on') {
                        if (isset($request['offpeak_control_load_two_usage']) && $request['offpeak_control_load_two_usage'] != '')
                            $controlLoadTwo = true;
                    }
                    if (isset($controlLoadOne) && isset($controlLoadTwo))
                        $meterType = 'demand_peak_c1_c2';
                    elseif (isset($controlLoadOne) && !isset($controlLoadTwo))
                        $meterType = 'demand_peak_c1';
                    elseif (!isset($controlLoadOne) && isset($controlLoadTwo))
                        $meterType = 'demand_peak_c2';
                    else
                        $meterType = 'demand_two_rate_only';
                } elseif ($request['meter_type'] == 'timeofuse') {
                    if ($request['shoulder_control_load'] && $request['shoulder_control_load'] == 'on') {
                        if (isset($request['shoulder_control_load_one_usage']) && $request['shoulder_control_load_one_usage'] != '')
                            $controlLoadOne = true;
                        if (isset($request['shoulder_control_load_two_usage']) && $request['shoulder_control_load_two_usage'] != '')
                            $controlLoadTwo = true;
                        if (isset($controlLoadOne) && isset($controlLoadTwo))
                            $meterType = 'demand_timeofuse_c1_c2';
                        elseif (isset($controlLoadOne) && !isset($controlLoadTwo))
                            $meterType = 'demand_timeofuse_c1';
                        elseif (!isset($controlLoadOne) && isset($controlLoadTwo))
                            $meterType = 'demand_timeofuse_c2';
                        else
                            $meterType = 'demand_timeofuse_only';
                    } else {
                        $meterType = 'demand_timeofuse_only';
                    }
                }
            } else {

                if ($request['meter_type'] == 'peak') {
                    $meterType = 'peak_only';
                } elseif ($request['meter_type'] == 'double') {
                    if (isset($request['control_load_one_usage']) && $request['control_load_one_usage'] != '')
                        $controlLoadOne = true;
                  
                        if (isset($request['control_load_two_usage']) && $request['control_load_two_usage'] != '')
                            $controlLoadTwo = true;
                    
                    if (isset($controlLoadOne) && isset($controlLoadTwo))
                        $meterType = 'peak_c1_c2';
                    elseif (isset($controlLoadOne) && !isset($controlLoadTwo))
                        $meterType = 'peak_c1';
                    elseif (!isset($controlLoadOne) && isset($controlLoadTwo))
                        $meterType = 'peak_c2';
                    else
                        $meterType = 'two_rate_only';

                } elseif ($request['meter_type'] == 'timeofuse') {
                   
                        if (isset($request['control_load_one_usage']) && $request['control_load_one_usage'] != '')
                            $controlLoadOne = true;
                        if (isset($request['control_load_two_usage']) && $request['control_load_two_usage'] != '')
                            $controlLoadTwo = true;
                        if (isset($controlLoadOne) && isset($controlLoadTwo))
                            $meterType = 'timeofuse_c1_c2';
                        elseif (isset($controlLoadOne) && !isset($controlLoadTwo))
                            $meterType = 'timeofuse_c1';
                        elseif (!isset($controlLoadOne) && isset($controlLoadTwo))
                            $meterType = 'timeofuse_c2';
                        else
                            $meterType = 'timeofuse_only';
                    } 
                }
            }
             else {
            $meterType = 'peak_only';
        }
        return $meterType;
    }
}
