<?php

namespace App\Traits\SolarType;

use Illuminate\Support\Facades\DB;

/**
* SolarType Methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{
     /** 
      * Get Data
      * Author: Sandeep Bangarh
     */
    static function getData($conditions, $columns = '*')
     {
          return DB::table('solar_plan_type')->select($columns)->where($conditions)->orderBy('state_code', 'ASC')->get();
     }
}