<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlansBroadbandTechnology extends Model
{
    use HasFactory;
    protected $fillable = [
        'technology_id',
        'plan_id'
    ];

}
