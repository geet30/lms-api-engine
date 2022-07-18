<?php
namespace App\Traits\Provider;
use App\Models\{
    Provider,ProviderLogo
};
trait CommonRelation
{ 
    public function providerLogo()
    {
        return $this->hasOne('App\Models\ProviderLogo', 'user_id','user_id')->where('category_id',9)->where('status',1);
    }    
}
