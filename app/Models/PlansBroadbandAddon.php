<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlansBroadbandAddon extends Model
{
    use HasFactory;

    public function plan(){
      return $this->belongsTo('App\Models\Plan','plan_id', 'id');
    } 

    public function masterAddon()
    {
        return $this->belongsTo('App\Models\PlanAddonsMaster','addon_id','id');
    }
}
