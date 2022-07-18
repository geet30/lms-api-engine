<?php
namespace App\Traits\MobilePlan;

/**
 * MobilePlan Relationship model.
 * Author: Sandeep Bangarh
 */

trait Relationship
{
    public function providers()
    {
        return $this->belongsTo('App\Models\Provider','provider_id', 'user_id');
    }
    public function contract()
    {
        return $this->belongsTo('App\Models\Contract','contract_id','id');
    }
    public function cost_type(){
       return $this->hasOne('App\Models\CostType','id','cost_type_id');
    }
    public function terms(){
    	return $this->hasMany('App\Models\PlanTelcoContents','plan_id')->whereNotNull('slug');
    }

    public function PlanHostType(){
      return $this->belongsTo('App\Models\ConnectionType','host_type','local_id')->where('service_id',2)->where('connection_type_id',4);
   }
    

    public function other_info(){
       return $this->hasMany('App\Models\PlanTelcoContents','plan_id')->where(function($q){
           $q->where('slug', '=', '')
             ->orWhereNull('slug');
       });
    }
    public function fees(){
      return $this->hasMany('App\Models\PlansTelcoFee','plan_id')->where('service_id',2);
   }

    

    public function contents(){
       return $this->hasMany('App\Models\PlanTelcoContents','plan_id');
    }


    public function planHandsets(){
       return $this->hasMany(\App\Models\PlanHandset::class,'plan_id');
    }

    public function PlanVariant(){
      return $this->hasMany(\App\Models\PlanVariant::class,'plan_id');
   }
}