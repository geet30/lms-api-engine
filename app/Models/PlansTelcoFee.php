<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlansTelcoFee extends Model
{
    use HasFactory;

    protected $fillable = ['service_id','fees','fee_id','cost_type_id','plan_id'];

    public function costType()
    {
        return $this->belongsTo('App\Models\CostType','cost_type_id','id');
    }

    public function feeType()
    {
        return $this->belongsTo('App\Models\Fee','fee_id','id');
    }
}
