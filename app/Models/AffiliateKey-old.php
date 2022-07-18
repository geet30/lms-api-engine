<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateKey extends Model
{
	use SoftDeletes;

    protected $table='affiliate_keys';

    protected $fillable=['user_id','name','api_key','unique_code','site_url','is_default','status','deleted_at','created_at','updated_at'];

 
}
