<?php

namespace App\Traits\User;

/**
* User Relationship model.
* Author: Sandeep Bangarh
*/

trait Relationship
{
    public function affiliate()
    {
        return $this->hasOne(\App\Models\Affiliate::class, 'user_id');
    }

    public function apiKeys()
    {
        return $this->hasMany(\App\Models\AffiliateKeys::class, 'user_id');
    }
    public function getUserAddress()
    {
        return $this->hasOne('App\Models\UserAddress', 'user_id');
    }
}
