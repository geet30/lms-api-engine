<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HandsetInfo extends Model
{
    protected $table = "hanset_more_infos";

    protected $fillable =['handset_id','image','title','s_no','status','linktype'];

    public function handset()
    {
        return $this->belongsTo('App\Models\Handset','handset_id','id');
    } 
}
