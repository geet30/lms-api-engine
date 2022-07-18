<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LogoCategory;

class ProviderLogo extends Model
{
    protected $table = 'provider_logos';

    // public function category()
    // {
    //     return $this->hasOne('App\Models\LogoCategory', 'id', 'category_id');
    // }

    // // scope method will return frontend Logo
    public function scopeFrontEndLogo($query, $value)
    {
        $category_id = LogoCategory::where('title', 'Frontend')->value('id');

        return $query->where('user_id', $value)->whereNull('deleted_at')->where('category_id', $category_id)->value('url');
    }
    public function getNameAttribute($value)
    {
        if (isset($value) && !empty($value)) {
            $providerName = $this->user_id;
            $s3fileName =   str_replace("<pro-id>", $providerName, config('env.PROVIDER_LOGO'));
            $url = config('env.Public_BUCKET_ORIGIN') . config('env.DEV_FOLDER') . $s3fileName . $value;
            return $url;
        }
        return $value;
    }
    const HOME_PAGE = 8;
    const STATUS_ENABLE = 1;
}
