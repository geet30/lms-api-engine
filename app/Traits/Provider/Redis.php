<?php

namespace App\Traits\Provider;

use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Predis\Connection\ConnectionException;

/**
* Lead Redis model.
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
			$isCacheEnabled = config('env.ENABLE_REDIS');
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
			$isCacheEnabled = config('env.ENABLE_REDIS');
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

	static function getCacheKey ($request, $prefix, $ignoreValues=['visit_id']) {
		$data = $request->all();
		$cacheKey = $prefix;
		foreach($data as $key => $value) {
			if (is_array($value)) {
				foreach($value as $vl) {
					$cacheKey .= $vl;
				}	
			} else {
				if (!in_array($key, $ignoreValues))
					$cacheKey .= $value;
			}
			
		}
		$cacheKey .= encryptGdprData($request->header('API-KEY'));
		return $cacheKey;
	}
}