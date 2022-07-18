<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class EnergyTariffInfo extends Model
{
    use HasFactory;
    
    protected $table="tariff_infos";
    protected $fillable = ['tariff_code_ref_id','tariff_code_aliases','tariff_discount','tariff_daily_supply','tariff_supply_discount','daily_supply_charges_description','discount_on_usage_description','discount_on_supply_description','plan_rate_ref_id','status','is_deleted'];

    public function tariffRates(){
        return $this->hasMany('App\Models\EnergyTariffRate','tariff_info_ref_id','id')->orderBy('season_rate_type')->orderBy(\DB::raw('FIELD(usage_type, "peak", "off_peak", "shoulder")'))->orderBy('limit_level');
    }
}
