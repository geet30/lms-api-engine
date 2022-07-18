<?php

namespace App\Traits\Otp;

use Illuminate\Support\Facades\DB;

/**
 * Otp Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    static function getData ($conditions, $columns) {
        return DB::table('lead_otps')->select($columns)->where($conditions)->first();
    }

    static function clearOTP($leadId) {
        self::where('lead_id', $leadId)->delete();
    }
}