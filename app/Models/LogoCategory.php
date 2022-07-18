<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogoCategory extends Model
{
    protected $fillable =['title'];

    public function logo(){
        return $this->belongsTo('App\Models\ProviderLogo','id','category_id');
    }
}
