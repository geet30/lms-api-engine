<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanContract extends Model
{
    protected $table='plans_mobile_contracts';

    protected $fillable=['plan_variant_id','plan_id','contract_id','contract_cost','contract_type','description','status'];

    public function contract()
    {
        return $this->belongsTo('App\Models\Contract','contract_id','id')->select('id','validity','contract_name');
        
    }
}
