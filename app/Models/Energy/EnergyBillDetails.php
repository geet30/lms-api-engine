<?php

namespace App\Models\Energy;

use Illuminate\Database\Eloquent\Model;
use App\Traits\EnergyLeadJourney\{Relationship, Methods};

class EnergyBillDetails extends Model
{
    use Relationship, Methods;

    protected $table = 'energy_bill_details';

    protected $fillable = [
        'energy_type',
        'lead_id',
        'current_provider_id',
        'bill_start_date',
        'bill_end_date',
        'bill_amount',
        'usage_level',
        'meter_type',
        'tariff_type',
        'solar_usage',
        'solar_tariff',
        'peak_usage',
        'off_peak_usage',
        'shoulder_usage',
        'control_load',
        'control_load_one_usage',
        'control_load_two_usage',
        'control_load_timeofuse',
        'control_load_one_off_peak',
        'control_load_one_shoulder',
        'control_load_two_off_peak',
        'control_load_two_shoulder',
        'demand_tariff',
        'demand_meter_type',
        'demand_usage_type',
        'demand_tariff_code',
        'demand_rate1_peak_usage',
        'demand_rate1_off_peak_usage',
        'demand_rate1_shoulder_usage',
        'demand_rate1_days',
        'demand_rate2_peak_usage',
        'demand_rate2_off_peak_usage',
        'demand_rate2_shoulder_usage',
        'demand_rate2_days',
        'demand_rate3_peak_usage',
        'demand_rate3_off_peak_usage',
        'demand_rate3_shoulder_usage',
        'demand_rate3_days',
        'demand_rate4_peak_usage',
        'demand_rate4_off_peak_usage',
        'demand_rate4_shoulder_usage',
        'demand_rate4_days',
    ];
    static function getBillDataCommon($leadId, $select)
    {

        return  self::where('lead_id', $leadId)->select($select)->get();
    }
}
