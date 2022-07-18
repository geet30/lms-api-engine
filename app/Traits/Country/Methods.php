<?php

namespace App\Traits\Country;
use Predis\Connection\ConnectionException;

/**
* Country methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{
    /*
     * Get all life support equipments titles.
     */
    static function isExist($countryId)
    {
        try {
            if (config('env.ENABLE_REDIS')) {
                $countryExistInCache = app('redis')->get('country:'.$countryId);
                if ($countryExistInCache) {
                    return true;
                }
            }
            
            $countryData = self::find($countryId);
            if (!$countryData) return false;

            if (config('env.ENABLE_REDIS')) {
                app('redis')->set('country:'.$countryId, $countryData->name);
            }
            
            return true;
        } catch (ConnectionException $e) {
            return self::whereId($countryId)->exists();
        }
    }
}