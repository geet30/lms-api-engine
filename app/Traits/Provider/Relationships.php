<?php

namespace App\Traits\Provider;

/**
* Lead Relationship model.
* Author: Sandeep Bangarh
*/

trait Relationships
{
    public function logo()
    {
        return $this->hasMany('App\Models\ProviderLogo', 'user_id', 'id')->orderBy('id', 'desc');
    }

    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }

    public function getUserAddress()
    {
        return $this->hasOne('App\Models\UserAddress', 'user_id', 'user_id');
    }
    
    public function provider_content(){
        return $this->hasMany('App\Models\ProviderContent','provider_id','user_id')->orderBy('id','desc');
    }

    public function provider_term_conditions() {
		return $this->hasMany('App\Models\ProviderContent','provider_id','user_id')->whereIn('type', [1,2,3,4,5,6,7,8,9]);
	}

    public function assigned_users(){
		return $this->hasMany('App\Models\AssignedUsers','relational_user_id','user_id')->where('relation_type', 1);
	}
}