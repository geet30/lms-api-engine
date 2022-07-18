<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\{Cache, DB};
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Handle an whitelisted and blocklisted IP's along with whitelisting or blocking all IP's from any country.
 * Author: Sandeep Bangarh
**/

class Firewall
{
    /**
     * Handle an incoming request.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next)
    {
        $isEnabled = config('app.ENABLE_FIREWALL');
        if (!$isEnabled) return $next($request);

        $isProduction = (@$_SERVER["REMOTE_ADDR"] != '127.0.0.1');
        if (!$isProduction) return $next($request);

        try {
            $ip = $request->ip();
            $cacheIP = Cache::get('whitelistip');
            $cacheIPCountry = Cache::get('whitelistip_country');
            $whiteListIPDateTime = Cache::get('whitelistip_date_time');

            /** if records are not recently updated and ip match with cached whitelisted IP then carry forward request **/
            if ($cacheIP && ($cacheIP == $ip || $cacheIPCountry) && $whiteListIPDateTime) {
                $recentlyUpdatedRecord = DB::table('firewall')->where(function ($query) use ($cacheIP, $cacheIPCountry) {
                    $query->where('ip_address', '=', $cacheIP)
                        ->orWhere('ip_address', '=', $cacheIPCountry);
                })->where('updated_at', '>=', $whiteListIPDateTime)->exists();

                if (!$recentlyUpdatedRecord) return $next($request);
            }

            $listedIpOrCode = DB::table('firewall')->select('ip_address', 'whitelisted')->where(function ($query) use ($ip, $cacheIPCountry) {
                $query->where('ip_address', '=', $ip)
                    ->orWhere('ip_address', '=', $cacheIPCountry);
            })->first();

            /** If IP is listed in white list then carry forward request **/
            if ($listedIpOrCode && $listedIpOrCode->whitelisted == '1') {
                if (!$cacheIP) {
                    $this->addIPOrCodeInCache($ip);
                }

                return $next($request);
            }

            if ($listedIpOrCode && $listedIpOrCode->whitelisted == 0 && $listedIpOrCode->ip_address == $ip) {
                return errorResponse('Request is forbidden', 403, 2012);
            }

            $isCountryCodeExistInDB = DB::table('firewall')->where('whitelisted', 1)->where('ip_address', 'LIKE', 'country:%')->exists();

            if (!$listedIpOrCode && $isCountryCodeExistInDB) {
                $countryCode = $this->getLocation($ip, 'countrycode');
                $whitelistedDataExist = DB::table('firewall')->where('whitelisted', 1)->where('ip_address', 'country:' . $countryCode)->exists();
                /** If IP's country is listed in white list then carry forward request **/
                if ($whitelistedDataExist) {
                    $this->addIPOrCodeInCache($ip, $countryCode);
                    return $next($request);
                }
            }

            /** Remove all stored cache keys **/
            $this->removeIPOrCodeFromCache();
            return errorResponse('Request is forbidden', 403, 2012);
        } catch (\Exception $e) {
            return $next($request);
        }
    }

    /**
     * Add IP or Code in Cache.
     * Author: Sandeep Bangarh
     * @param  string  $ip
     * @param  string  $countryCode
     * @return boolean
     */
    private function addIPOrCodeInCache($ip, $countryCode = null)
    {
        $minutes = 120;
        Cache::put('whitelistip', $ip, $minutes);
        if ($countryCode) {
            Cache::put('whitelistip_country', 'country:' . $countryCode, $minutes);
        }
        Cache::put('whitelistip_date_time', Carbon::now()->toDateTimeString(), $minutes);
        return true;
    }

    /**
     * Remove IP or Code in Cache.
     *
     * @return boolean
     */
    private function removeIPOrCodeFromCache()
    {
        $cacheVariables = ['whitelistip', 'whitelistip_country', 'whitelistip_date_time'];
        foreach ($cacheVariables as $key) {
            Cache::forget($key);
        }
        return true;
    }

    /**
     * Validate Country and IP.
     * Author: Sandeep Bangarh
     * @param  string  $countryOrIp
     * @return boolean
     */
    private function validCountryOrIp($countryOrIp)
    {
        $countryOrIp = strtolower($countryOrIp);
        if (filter_var($countryOrIp, FILTER_VALIDATE_IP)) {
            return true;
        }

        $countries = @json_decode(file_get_contents(public_path('geoip/countries.json')), true);
        $countryCodes = array_column($countries, 'code');
        if (Str::startsWith($countryOrIp, 'country:') && in_array(strtoupper(str_replace("country:", "", $countryOrIp)), $countryCodes)) {
            return true;
        }

        return false;
    }

    /**
     * Validate Country and IP.
     * Author: Sandeep Bangarh
     * @param  string  $ip
     * @param  string  $purpose
     * @return mixed
     */
    public function getLocation($ip, $purpose = 'country')
    {
        if ($this->validCountryOrIp($ip)) {
            $ch = curl_init('http://ipwhois.app/json/' . $ip);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $json = curl_exec($ch);
            curl_close($ch);
            $ipWhoIsResponse = @json_decode($json, true);
            $purpose    = str_replace(array("name", "\n", "\t", " ", "-", "_"), '', strtolower(trim($purpose)));
            $output = '';
            switch ($purpose) {
                case "city":
                    $output = @$ipWhoIsResponse['city'];
                    break;
                case "state":
                    $output = @$ipWhoIsResponse['region'];
                    break;
                case "region":
                    $output = @$ipWhoIsResponse['region'];
                    break;
                case "country":
                    $output = @$ipWhoIsResponse['country'];
                    break;
                case "countrycode":
                    $output = @$ipWhoIsResponse['country_code'];
                    break;
            }
            return strtolower($output);
        }
        return false;
    }
}
