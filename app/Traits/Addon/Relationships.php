<?php

namespace App\Traits\Addon;

use App\Models\{Service, Provider, PlanEnergy, PlansBroadband, PlanMobile, SaleProductsBroadbandAddon};

/**
 * Lead Relationship model.
 * Author: Sandeep Bangarh
 */

trait Relationships
{

    public function homeConnection()
    {
        return $this->belongsTo('App\Models\PhoneHomeLineConnection', 'addon_id', 'id');
    }

    public function broadBandModem()
    {
        return $this->belongsTo('App\Models\BroadbandModem', 'addon_id', 'id');
    }

    public function broadBandOtherAddon()
    {
        return $this->belongsTo('App\Models\BroadbandAdditionalAddons', 'addon_id', 'id');
    }

    public function cost_type()
    {
        return $this->hasOne('App\Models\CostType', 'id', 'cost_type')->where(
            function ($q) {
                $q->where('status', '=', '1')
                    ->where('is_deleted', '=', '0');
            }
        );
    }
}
