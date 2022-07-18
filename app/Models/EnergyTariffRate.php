<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class EnergyTariffRate extends Model
{
    use HasFactory;
    protected $table = "tariff_rates";
    protected $fillable=['tariff_info_ref_id','season_rate_type','usage_type','limit_level','limit_charges','limit_daily','limit_yearly','usage_discription'];
}
