<?php

namespace App\Traits\Plan\Energy;

/**
* Plan Relationship model.
* Author: Sandeep Bangarh
*/

trait Relationships
{
    public function planEicContents()
    {
        return $this->hasOne('App\Models\PlanEicContent', 'plan_id', 'id')->where('status', 1);
    }

    function provider()
    {
        return $this->hasOne('App\Models\Provider', 'user_id', 'provider_id');
    }
}