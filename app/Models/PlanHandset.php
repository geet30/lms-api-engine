<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class PlanHandset extends Model
{
    protected $table = 'plans_mobile_handsets';
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'id', 'plan_id');
    }

    public function handset()
    {
        return $this->belongsTo('App\Models\Handset', 'handset_id')->select('id','name','model','launch_detail');
    }

    // scope method for api only
    public function scopeApiStatus($query)
    {
        return $query->where('master_status', 1)->where('provider_status', 1)->where('status', 1);
    }
}
