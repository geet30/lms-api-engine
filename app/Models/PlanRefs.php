<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanRefs extends Model
{
    protected $table = 'plan_mobile_references';
    protected $fillable = ['plan_id','s_no','title','url','status'];

}
