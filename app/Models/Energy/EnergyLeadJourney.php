<?php

namespace App\Models\Energy;

use Illuminate\Database\Eloquent\Model;

use App\Traits\EnergyLeadJourney\{Relationship, Methods};

use App\Models\Visitor;

class EnergyLeadJourney extends Model
{
    use Relationship, Methods;

    protected $table = 'lead_journey_data_energy';

    protected $fillable = ['lead_id', 'distributor_id', 'previous_provider_id', 'bill_available', 'is_dual', 'plan_bundle_code', 'property_type', 'energy_type', 'solar_panel', 'solar_options', 'life_support', 'life_support_energy_type', 'life_support_value', 'moving_house', 'moving_date', 'prefered_move_in_time', 'elec_concession_rebate_ans', 'elec_concession_rebate_amount', 'gas_concession_rebate_ans', 'gas_concession_rebate_amount', 'screen_name', 'step_name', 'percentage','filters','credit_score'];
  
    
}
