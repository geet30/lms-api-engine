<?php

namespace App\Traits\Product;

use App\Models\ { Service, Provider, PlanEnergy, PlansBroadband, PlanMobile, SaleProductsBroadbandAddon, Handset, Variant, Contract, Color,MobileConnectionDetails };

/**
* Lead Relationship model.
* Author: Sandeep Bangarh
*/

trait Relationships
{

    public function provider() {
        return $this->hasOne(Provider::class, 'user_id', 'provider_id')->whereStatus(1);
    }

    public function planEnergy() {
        return $this->hasOne(PlanEnergy::class, 'id', 'plan_id')->whereStatus(1);
    }

    public function planBroadband() {
        return $this->hasOne(PlansBroadband::class, 'id', 'plan_id')->whereStatus(1);
    }

    public function planMobile() {
        return $this->hasOne(PlanMobile::class, 'id', 'plan_id')->whereStatus(1);
    }

    public function service() {
        return $this->hasOne(Service::class, 'id', 'service_id')->whereStatus(1);
    }

    public function addons() {
        return $this->hasMany(SaleProductsBroadbandAddon::class, 'sale_product_id', 'product_id')->select('id','sale_product_id','category_id','addon_id','addon_type','cost','cost_type')->where('is_mandatory', 0);
    }

    public function handset () {
        return $this->hasOne(Handset::class, 'id', 'handset_id')->whereStatus(1);
    }

    public function variant () {
        return $this->hasOne(Variant::class, 'id', 'variant_id')->whereStatus(1);
    }

    public function contract () {
        return $this->hasOne(Contract::class, 'id', 'contract_id')->whereStatus(1);
    }

    public function color () {
        return $this->hasOne(Color::class, 'id', 'color_id')->whereStatus(1);
    }
    public function mobileConnection () {
        return $this->hasOne(MobileConnectionDetails::class, 'mobile_connection_id', 'id');
    }

}