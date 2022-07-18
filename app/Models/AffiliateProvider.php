<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffiliateProvider extends Model
{
	/*use SoftDeletes;*/

    protected $table='affiliate_providers';

   /* protected $fillable=['user_id','name','api_key','site_url','status','deleted_at','created_at','updated_at'];*/
     
    public function providers(){
     	return $this->hasOne('App\Models\Provider','id','user_id');
    }
}
