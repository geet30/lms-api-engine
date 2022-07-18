<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Broadband\BasicCrudMethods;
use App\Traits\Provider\{CommonRelation, Relationships, Accessor, Methods, Redis};


class Provider extends Model
{

    use BasicCrudMethods, CommonRelation, Relationships, Accessor, Methods, Redis;

    protected $fillable = ['user_id', 'address_id', 'service_id', 'name', 'legal_name', 'abn_acn_no', 'support_email', 'complaint_email', 'description', 'demand_usage_check', 'status'];
    const STATUS_ENABLE = 1;
    const ZERO = 0;
    const SERVICE_BROADBAND = 3;
    const SERVICE_MOBILE = 2;


    public function logo()
    {
        return $this->hasMany('App\Models\ProviderLogo', 'user_id', 'user_id')->orderBy('id', 'desc');
    }
    public function PlanListLogo()
    {
        return $this->hasOne('App\Models\ProviderLogo', 'user_id', 'user_id')->where('category_id', '9');
    }
    public function content()
    {
        return $this->hasOne('App\Models\ProviderContent', 'provider_id', 'user_id')->orderBy('id', 'desc');
    }
    public function acknowledgementContent()
    {
        return $this->hasOne('App\Models\ProviderContent', 'provider_id', 'user_id')->where('type', "17")->orderBy('id', 'desc');
    }
    public function EnergyProviderContent()
    {
        return $this->hasOne('App\Models\ProviderContent', 'provider_id', 'user_id');
    }
    public function ProviderDirectDebit()
    {
        return $this->hasOne('App\Models\ProviderDirectDebit', 'user_id', 'user_id');
    }
    protected $appends = ['logo'];

    static function getProviders($service)
    {
        return self::select('user_id', 'name', 'legal_name')->where('service_id', $service)->where('status', 1)->where('is_deleted', 0)->get()->toArray();
    }

    // public function getNameAttribute($value)
    // {
    //     return  decryptGdprData($value);
    // }
    public function getHandset()
    {
        return $this->hasMany('App\Models\ProviderVariant', 'provider_id', 'user_id');
    }
}
