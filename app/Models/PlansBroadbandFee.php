<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlansBroadbandFee extends Model
{
    use HasFactory;
    public function fees()
    {
        return $this->hasOne('App\Models\Fee', 'id','fee_id');
    }
}
