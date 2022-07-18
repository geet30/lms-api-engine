<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class UserStates extends Model
{
    protected $table = 'user_states';
	
    public function state()
    {
        return $this->belongsTo('App\Models\States','state_id','state_id');
    }

    public function userSubrubs()
    {
        return $this->hasMany('App\Models\UserSuburb','state_id','state_id')->where('status',1);
    }
}