<?php

namespace App\Http\Controllers\V1\Auth;

use App\Models\{Service, AffiliateKeys};
use Tymon\JWTAuth\JWT;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GenerateTokenController
{
    /**
     * Generate Token.
     * Author: Sandeep Bangarh
     * @param  \Tymon\JWTAuth\JWT  $jwt
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(JWT $jwt)
    {
        try {
            $request = app('request');
            $apiKey = $request->header('api_key');
            $countryId = request()->header('CountryId');

            if (!$apiKey) {
                return errorResponse('API-Key is required', HTTP_STATUS_VALIDATION_ERROR, TOKEN_VALIDATION_CODE);
            }

            $tokenArr = $this->getTokenFromCache();
            if ($tokenArr['token']) {
                return successResponse('Token generated successfully', TOKEN_SUCCESS_CODE, ['token' => $tokenArr['token'], 'token_expire_time' => $tokenArr['date'], 'services' => $tokenArr['services']]);
            }

            $validated = AffiliateKeys::validateAffiliate($apiKey);
            if (!$validated) {
                return errorResponse('This account is disabled/API disabled.', HTTP_STATUS_VALIDATION_ERROR, TOKEN_VALIDATION_CODE);
            }
            try {
                $token = $jwt->claims(['api_key' => $countryId . '_' . $apiKey])->fromUser($validated);
            } catch (\Exception $e) {
                return errorResponse('Unauthorized', $e->getCode(), AUTH_ERROR_CODE);
            }

            $services = Service::getServices($validated->id);
            $minutes = $this->addTokenInCache($token, $services);

            $hours = (int) floor($minutes / 60);
            $expireTime = Carbon::now()->addHours($hours);

            return successResponse('Token generated successfully', TOKEN_SUCCESS_CODE, ['token' => $token, 'token_expire_time' => $expireTime->toDateTimeString(), 'services' => Service::getServices($validated->id)]);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), TOKEN_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Add token in cache.
     * Author: Sandeep Bangarh
    */
    protected function addTokenInCache($token, $services)
    {
        $countryId = (string) request()->header('CountryId');
        $apiKey = request()->header('api_key');
        $ipAddress = (string) request()->ip();
        $tokenDateKey = $apiKey.'_'.$countryId . '_' . $ipAddress . '_token_date';
        $serviceKey = $apiKey.'_'.$countryId . '_' . $ipAddress . '_token_services';
        $tokenKey = $apiKey.'_'.$countryId . '_' . $ipAddress . '_token';
        $minutes = config('jwt.ttl');
        $startTime = Cache::get($tokenDateKey);
        if ($startTime) {
            $currentDate = Carbon::now();
            $totalMinutes = $currentDate->diffInMinutes($startTime);
            if ($totalMinutes && $totalMinutes <= $minutes) {
                $minutes = $minutes - $totalMinutes;
            }
        } else {
            Cache::put($tokenDateKey, Carbon::now()->toDateTimeString(), $minutes);
        }

        Cache::put($tokenKey, $token, $minutes);
        Cache::put($serviceKey, json_encode($services), $minutes);

        return $minutes;
    }

    protected function getTokenFromCache()
    {
        $apiKey = request()->header('api_key');
        $takeFromCache = config('app.CACHE_SERVICES');
        $countryId = (string) request()->header('CountryId');
        $ipAddress = (string) request()->ip();
        $startTime = Cache::get($apiKey.'_'.$countryId . '_' . $ipAddress . '_token_date');
        $services = Cache::get($apiKey.'_'.$countryId . '_' . $ipAddress . '_token_services');
        if ($services) {
            $services = json_decode($services);
        }
        
        if (!$takeFromCache) {
            $validated = AffiliateKeys::validateAffiliate($apiKey);
            if ($validated) $services = Service::getServices($validated->id);
        }
        
        return ['date'=>$startTime, 'token' => Cache::get($apiKey.'_'.$countryId . '_' . $ipAddress . '_token'), 'services'=> $services];
    }
}
