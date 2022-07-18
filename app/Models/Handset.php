<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Handset extends Model
{
    protected $table = 'handsets';

    protected $fillable = ['name', 'brand_id', 'model', 'launch_detail', 'is_pre_order', 'why_this', 'other_info', 'warranty', 'technology', 'network_managebility', 'extra_technology', 'dimension', 'weight', 'body_protection_build', 'sim_compatibility', 'is_card_slot', 'screen_type', 'screen_size', 'screen_resolution', 'multitouch', 'screen_protection', 'os', 'version', 'chipset', 'cpu', 'internal_storage', 'camera', 'sensors', 'technical_specs', 'battery_info', 'in_the_box', 'status'];


    public function brand()
    {
        return $this->belongsTo('App\Models\Brand', 'brand_id', 'id');
        //return $this->hasOne('App\Models\Brand','id','brand_id');
    }

    public function info()
    {
        return $this->hasMany('App\Models\HandsetInfo', 'handset_id', 'id');
    }

    public function variants()
    {
        return $this->hasMany('App\Models\Variant', 'handset_id', 'id');
    }
    public function opratingSystem()
    {
        return $this->belongsTo('App\Models\SystemInfo', 'os', 'id');
    }

    /*
    * use for api only.
    */
    public function variants_list()
    {
        return $this->hasMany('App\Models\Variant', 'handset_id', 'id')->groupBy('capacity_id', 'internal_stroage_id');
    }

    public function Provider()
    {
        return $this->hasMany('App\Models\Provider', 'provider_id', 'id');
    }
    public function Providerhandset()
    {
        return $this->belongsTo('App\Models\ProviderHandset', 'provider_id', 'id');
    }


    // public function assign_variant()
    // {
    //     return $this->hasMany('App\Models\ProviderVariant','varient_id','id');
    // }

    public function assign_provider()
    {
        return $this->hasMany('App\Models\ProviderHandset', 'handset_id', 'id');
    }

    public function planhandset()
    {
        return $this->hasMany('App\Models\PlanHandset', 'handset_id', 'id');
    }


    public static function boot()
    {
        parent::boot();
        //$provider = new Provider();
        static::deleting(function ($handset) { // before delete() method call this

            $handset->variants()->delete();
            $handset->info()->delete(); // delete contacts of provider

        });
    }


    /******************Relation Used For API purpose To overcome Encrypted ID********************/
    public function api_brand()
    {
        return $this->belongsTo('App\Models\Brand', 'brand_id', 'brand_unique_id');
    }
    public function getImageAttribute($value)
    {
        if (isset($value) && !empty($value)) {
            $handsetId = $this->id;
            $s3fileName =   str_replace("<handset_id>", $handsetId, config('env.HANDSET_LOGO'));
            $url = config('env.Public_BUCKET_ORIGIN') . config('env.DEV_FOLDER') . $s3fileName . $value;
            return $url;
        }
        return $value;
    }
}
