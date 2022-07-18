<?php
namespace App\Traits\Broadband;

trait Relationship
{
    public function technologies()
    {
        return $this->belongsToMany('App\Models\ConnectionType','plans_broadband_technologies','plan_id','technology_id');
        //return $this->hasMany('App\Models\PlansBroadbandTechnology','plan_id','id');
    }

    public function providers()
    {
        return $this->hasOne('App\Models\Provider', 'user_id','provider_id');
    }

    public function contracts()
    {
        return $this->hasOne('App\Models\Contract', 'id','contract_id');
    }

    public function planfees()
    {
        return $this->hasOne('App\Models\PlansTelcoFee', 'plan_id','id')->where('service_id',3);
    }

    public function planEicContents()
    {
        return $this->hasOne('App\Models\PlansBroadbandEicContent', 'plan_id', 'id');
    }

    function planEicContentCheckbox()
    {
        return $this->hasMany('App\Models\PlansBroadbandContentCheckbox', 'plan_id', 'id');
    }
    
    function provider()
    {
        return $this->hasOne('App\Models\Provider', 'user_id', 'provider_id');
    }
            
}
