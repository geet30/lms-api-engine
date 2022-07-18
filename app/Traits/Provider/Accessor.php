<?php

namespace App\Traits\Provider;

use Illuminate\Support\Facades\DB;

/**
 * Provider Accessor model.
 * Author: Sandeep Bangarh
 */

trait Accessor
{
    /**
     * Get the user's first name.
     *
     * @param  string  $value
     * @return string
     */
    public function getLogoAttribute()
    {
        $logo = DB::table('provider_logos')->select('name','url')->where('user_id', $this->user_id)->where('category_id', 9)->first();
        if (!empty($logo->name)) {
            $s3fileName =   str_replace("<pro-id>", $this->user_id, config('env.PROVIDER_LOGO'));
            $url = config('env.Public_BUCKET_ORIGIN') . config('env.DEV_FOLDER') . $s3fileName . $logo->name;
            return $url;
        }
        return $logo ? $logo->url : '';
    }

    /**
     * Get decrypted Legal name.
     *
     * @param  string  $value
     * @return string
     */
    // public function getLegalNameAttribute($value)
    // {
    //     return decryptGdprData($value) ? decryptGdprData($value) : $value;
    // }
    // public function getNameAttribute($value)
    // {
    //     return decryptGdprData($value) ? decryptGdprData($value) : $value;
    // }
}
