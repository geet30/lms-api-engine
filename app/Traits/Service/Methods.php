<?php

namespace App\Traits\Service;
use Illuminate\Support\Facades\DB;

/**
* Service Methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{
    /**
     * Get Services or Single Service.
     * Author: Sandeep Bangarh
     * @param array|string $columns
     * @return \Illuminate\Support\Collection
    */
    static function getServices ($userId, $serviceId=null) {
        $query = DB::table('services')
        ->select('services.id','service_title','journey_order','position')
        ->distinct()
        ->join('user_services', 'services.id', '=', 'user_services.service_id')
        ->where('user_services.user_id', $userId)
        ->where('services.status', 1)
        ->where('user_services.status', 1)
        ->orderBy('position');
        if ($serviceId) {
            return $query->where('services.id', $serviceId)->first();
        }
        return $query->get();
    }
}