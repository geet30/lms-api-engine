<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variant_images extends Model
{

protected $fillable = ['handset_id','variant_id','sr_no','type','image','status','deleted_at'];

    public function variant()
    {
        return $this->belongsTo('App\Models\Variant','id','variant_id');
    }

}
