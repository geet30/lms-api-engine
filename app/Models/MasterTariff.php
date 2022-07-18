<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MasterTariff\ { Methods, Relationship};
class MasterTariff extends Model
{

    use Methods, Relationship;
    protected $fillable = ['tariff_code','tariff_type','master_tariff_ref_id','property_type','distributor_id','units_type','status','created_at','updated_at'];

    protected $appends = [ 'usagePrefix' ];
    protected $dates = ['deleted_at'];


    public function tariffTypes(){
        return $this->belongsTo('\App\models\MasterTariff','master_tariff_ref_id','id');
    }
    public function distributor(){
        return $this->belongsTo('\App\models\Distributor','distributor_id','id');
    }

    public function getUsagePrefixAttribute () {
        if ($this->units_type == 1) return 'cents per kVA';
        else return 'cents per kWh';
    }
    
   
 
}
