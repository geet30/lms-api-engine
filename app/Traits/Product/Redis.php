<?php

namespace App\Traits\Product;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Predis\Connection\ConnectionException;

/**
* Product Redis model.
* Author: Sandeep Bangarh
*/

trait Redis
{
/**
	 * Add plan listing into redis cache
	 * Author: Sandeep Bangarh
	*/
	static function addDataIntoCache ($cacheKey, $data) {
		try {
			$isCacheEnabled = config('app.ENABLE_REDIS_CACHE');
			if ($isCacheEnabled && $cacheKey) {
				$minutes = config('jwt.ttl');
				$startTime = Cache::get('redis_date_time');
				if ($startTime) {
					$currentDate = Carbon::now();
					$totalMinutes = $currentDate->diffInMinutes($startTime);
					if ($totalMinutes && $totalMinutes <= $minutes) {
						$minutes = $minutes - $totalMinutes;
					}
				} else {
					Cache::put('redis_date_time', Carbon::now()->toDateTimeString(), $minutes);
				}
				
				app('redis')->setex($cacheKey, $minutes*60, json_encode($data));
				
			}
		} catch (ConnectionException $e) {
			return $e->getMessage();
		}
	}

	/**
	 * Get saved Plan listing
	 * Author: Sandeep Bangarh
	*/
	static function getDataFromCache ($cacheKey) {
		try {
			$isCacheEnabled = config('app.ENABLE_REDIS_CACHE');
			if ($isCacheEnabled && $cacheKey) {
				$listing = app('redis')->get($cacheKey);
				if ($listing) {
					return json_decode($listing, true);
				}
			}
			
			return false;
		} catch (ConnectionException $e) {
			return false;
		}
	}
}