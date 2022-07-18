<?php

namespace App\Traits\Distributor;

/**
* Distributor Relationship model.
* Author: Sandeep Bangarh
*/

trait Relationship
{
	public function postcode()
	{
		return $this->hasMany('App\Models\DistributorPostCode', 'distributor_id');
	}
}