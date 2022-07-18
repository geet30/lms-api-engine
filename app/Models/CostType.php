<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class CostType extends Model
{
	protected $table = 'cost_types';

     protected $fillable = ['cost_name','order','cost_period'] ;
}
