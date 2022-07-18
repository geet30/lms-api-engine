<?php

namespace App\Traits\Customer;

use Illuminate\Support\Facades\DB;

/**
* Customer Closure model.
* Author: Sandeep Bangarh
*/

trait Closure
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::updated(function ($visitor) {
            self::addLogs($visitor);
        });

        static::retrieved(function ($visitor) {
            foreach(self::$gdprFields as $gdprField) {
                $visitor->{$gdprField} = decryptGdprData($visitor->{$gdprField});
            }
        });
    }

    static function addLogs ($visitor) {
        $leadId = decryptGdprData(request('visit_id'));
        DB::table('visitor_logs')->insert([  'lead_id' => $leadId, 'visitor_id' => $visitor->id, 'phone' => $visitor->phone ]);
    }
}