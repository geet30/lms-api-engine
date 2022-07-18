<?php

namespace App\Models\Plans;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyMasterTariff extends Model
{
    use HasFactory;
    protected $table = 'master_tariffs';
    protected $fillable=['tariff_type','tariff_code','master_tariff_ref_id','property_type','distributor_id','units_type','status','is_deleted'];

    public function distributor(){
        return $this->belongsTo('\App\Models\Distributor','distributor_id','id');
    }
}
