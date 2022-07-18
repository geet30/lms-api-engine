<?php

namespace App\Traits\LifeSupport;

/**
* LifeSupport Methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{
    /*
     * Get all life support equipments titles.
     * Author: Sandeep Bangarh
     */
    static function lifeSupportEquipments()
    {
        $lifeArray = self::select('title')->where('status', '1')->orderBy('title', 'ASC')->pluck('title')->toArray();
        if (($key = array_search('other', array_map('strtolower', $lifeArray))) !== false) {
            $unsetValue = $lifeArray[$key];
            unset($lifeArray[$key]);
            array_push($lifeArray, $unsetValue);
        }
        $lifeSupportTitles = array_combine($lifeArray, $lifeArray);
        return $lifeSupportTitles;
    }
}