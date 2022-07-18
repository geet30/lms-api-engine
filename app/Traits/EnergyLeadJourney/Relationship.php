<?php

namespace App\Traits\EnergyLeadJourney;
use App\Models\Energy\EnergyBillDetails;
/**
 * EnergyLeadJourney Relationship model.
 * Author: Sandeep Bangarh
 */

trait Relationship
{

    public function  billData(){
        return $this->hasOne(EnergyBillDetails::class,'lead_id','lead_id')->where('energy_type',1);

    }
    public function visitorData(){

        return $this->hasOne(Visitor::class);
    }
}
