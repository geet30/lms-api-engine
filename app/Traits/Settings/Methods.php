<?php

namespace App\Traits\Settings;

use Illuminate\Support\Facades\DB;
use App\Models\Lead;

/**
 * Settings Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    /**
     * Get settings.
     * Author: Sandeep Bangarh
     * @param array|string $conditions $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
     */
    static function getAppSetting($conditions, $columns = '*')
    {
        $service = Lead::getService(true);
        return DB::table('app_settings')->select($columns)->where('service_id', $service)->where($conditions)->first();
    }

    /**
     * Get settings.
     * Author: Sandeep Bangarh
     * @param array|string $conditions $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
     */
    static function getSetting($conditions, $columns = '*')
    {
        return DB::table('settings')->select($columns)->where($conditions)->first();
    }

    static function isDmoState($lead)
    {
        $is_dmo_state = 0;
        if ($lead->post_code) {
            $dmoState = static::getSetting(['key' => 'dmo_state']);
            if ($dmoState) {
                $dmoState = $dmoState->value;
                $dmoState = explode(",", $dmoState);
                $state = explode(",", $lead->post_code);
                $state = trim($state[2]);
                if (in_array($state, $dmoState)) {
                    $is_dmo_state = 1;
                }
            }
        }
        return $is_dmo_state;
    }

    static function getTermOrPrivacyData($conditions, $columns)
    {
        $setting = self::getAppSetting($conditions, $columns);
        if ($setting) {
            $attributes = explode(",", $setting->attributes);
            /** function to get User data from Affilate id **/
            $userData = auth()->user();
            /** Prepare data to override the attributes in the content with Original one. */
            $img = "no_image.jpg";
            if (!empty($userData->photo)) {
                $img = $userData->photo;
            }

            $affiliateData = (array) $userData->getAffiliate(['ivr_number', 'website_url', 'facebook_url', 'twitter_url', 'youtube_url']);
            $affiliateData['photo'] = "<img src='" . url('uploads/profile_images/' . $img) . "'/>";
            $affiliateData['first_name'] = $userData->first_name;
            return str_replace($attributes, $affiliateData, $setting->content);
        }
    }
}
