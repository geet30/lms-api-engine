<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTelcoContents extends Model
{
	protected $table = 'plans_telco_contents';
	protected $fillable=['plan_id','title','description','slug'];
}
