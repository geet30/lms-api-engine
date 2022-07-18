<?php

namespace App\Traits\Keys;

/**
* Keys Relationship model.
* Author: Sandeep Bangarh
*/

trait Relationship
{
	public function user()
	{
		return $this->belongsTo('App\Models\User');
	}

	public function affiliate()
	{
		return $this->belongsTo('App\Models\Affiliate', 'user_id', 'user_id');
	}
}