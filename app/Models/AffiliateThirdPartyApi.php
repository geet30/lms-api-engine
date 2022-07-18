<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateThirdPartyApi extends Model
{

    protected $table = 'affiliate_third_party_apis'; 
    protected $fillable = ['user_id','third_party_platform','spark_post_api_key','affiliate_sub_acc_id','status'];
	
}
