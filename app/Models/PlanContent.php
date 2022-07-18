<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanContent extends Model
{
	protected $fillable=['plan_id','title','description','slug'];
}
