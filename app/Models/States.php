<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class States extends Model
{
	protected $table = 'states';

	public function suburbs()
    {
        return $this->hasMany('App\Models\Postcode','state','state_code');
    }
}