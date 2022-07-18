<?php

namespace App\Traits\Marketing;

/**
* Marketing Methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{

    /**
    * Add marketing paraemters.
    * Author: Sandeep Bangarh
    */
    static function addParamaeters ($leadId, $utmData) {
        return self::updateOrCreate(['lead_id' => $leadId], $utmData);
    }
}