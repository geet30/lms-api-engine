<?php
namespace App\Repositories\Provider;
use App\Models\{
    Provider,
};
trait Common
{ 
    
    /**
     * get list of all Addon
     * 
     * @param  int  $planId
     * @return \Illuminate\Http\Response
     */
    public static function getProviderLogo()
    {
        return $this->hasOne('App\Models\ProviderLogo', 'user_id','user_id');
    }
}
