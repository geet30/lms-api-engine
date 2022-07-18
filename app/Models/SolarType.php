<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SolarType\ { Methods };

/**
* SolarType Model.
* Author: Sandeep Bangarh
*/

class SolarType extends Model
{

     use Methods;

     protected $table = 'solar_plan_type';
     protected $fillable = ['state_code', 'is_premium', 'is_normal'];
}
