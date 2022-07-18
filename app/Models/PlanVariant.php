<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanVariant extends Model
{
    protected $table = 'plans_mobile_variants';
    // reverse database relationship
    public function variant()
    {
        return $this->belongsTo('App\Models\Variant', 'variant_id');
    }


    protected $fillable = ['status'];

    public function scopeApiStatus($query)
    {
        return $query->where('master_status', 1)->where('provider_status', 1)->where('status', 1);
    }


    public function PlanContracts()
    {
        return $this->hasMany(PlanContract::class,'plan_variant_id', 'id');
    }

    public function plan()
    {
        return $this->belongsTo(PlanMobile::class, 'plan_id', 'id');
    }
    public function handset()
    {
        return $this->belongsTo(Handset::class);
    }
}
