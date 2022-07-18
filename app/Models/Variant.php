<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
   protected $table = 'handset_variant';

   protected $fillable = ['handset_id','variant_id','variant_name','capacity_id','internal_stroage_id','color_id','status'];

    public function color()
    {
        return $this->hasOne('App\Models\Color','id','color_id');
    }

    public function capacity()
    {
        return $this->hasOne('App\Models\Capacity','id','capacity_id');
    }

    public function internal()
    {
        
        return $this->hasOne('App\Models\InternalStorage','id','internal_stroage_id');
    }

    public function images()
    {
        return $this->hasMany('App\Models\Variant_images','variant_id','id')->orderBy('sr_no')->limit(1);
    }

    public function all_images()
    {
        return $this->hasMany('App\Models\Variant_images','variant_id','id')->orderBy('sr_no')->whereNull('deleted_at');
    }
 

    // common method trigger while deleting variant. this will delete all related relational data.
    public static function boot() {
        parent::boot();
       
        static::deleting(function($variant) { // before delete() method call this
            $variant->all_images()->delete();
        });
    }


    /******************Relation Used For API purpose To overcome Encrypted ID********************/
    public function api_capacity(){
        return $this->hasOne('App\Models\Capacity','capacity_unique_id','capacity_id');
    }
   
    public function api_internal(){
        return $this->hasOne('App\Models\InternalStorage','storage_unique_id','internal_stroage_id');
    }
    public function storage(){
        return $this->hasOne('App\Models\InternalStorage','id','internal_stroage_id');
    }
    public function api_handset(){
        return $this->belongsTo('App\Models\Handset','handset_id','id');
    }
    public function api_color(){
        return $this->belongsTo('App\Models\Color','color_id','color_unique_id');
    }

}
