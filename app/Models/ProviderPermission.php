<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Provider\Methods;

class ProviderPermission extends Model
{
    use HasFactory,Methods;
    protected $table = 'provider_permissions';
    const TYPE=22;

    protected $fillable = ['user_id','is_new_connection','is_port','is_retention','is_life_support','life_support_energy_type','is_submit_sale_api','is_resale','is_gas_only','is_demand_usage','ea_credit_score_allow','credit_score','is_telecom','is_send_plan','deleted_at','connection_script','port_script','recontract_script','is_sclerosis','is_medical_cooling','sclerosis_title','medical_cooling_title'];

    public function checkbox()
    {
        return $this->hasMany('App\Models\ProviderContentCheckboxes', 'provider_content_id', 'id');
    }

}
