<?php

namespace App\Traits\Affiliate;

/**
* Affiliate relation model.
* Author: Sandeep Bangarh
*/

trait Relationship
{
	public function user()
	{
		return $this->belongsTo('App\Models\User');
	}

	public function subaffiliate()
	{
		return $this->belongsTo('App\Models\Affiliate', 'parent_id');
	}

	public function keys()
	{
		return $this->hasMany('App\Models\AffiliateKeys', 'user_id', 'user_id')->where('status', '1')->whereNotNull('api_key')->where('api_key','!=','')->select('api_key');
	}

	public function parent()
	{
		return $this->belongsTo('App\Models\Affiliate', 'parent_id')->where('status', 1);
	}
	public function affiliateParameter(){

		return $this->hasOne('App\Models\AffiliateParamter', 'user_id', 'user_id');

		
	}
}