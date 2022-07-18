<?php

namespace App\Traits\Service;

/**
* Service Relationship model.
* Author: Sandeep Bangarh
*/

trait Relationship
{
	public function users() {
    	return $this->belongsToMany(\App\Models\User::class, 'user_services','service_id','user_id');
    }
}