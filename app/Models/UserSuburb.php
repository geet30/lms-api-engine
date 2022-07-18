<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSuburb extends Model
{
    use HasFactory;

    public function subrubs()
    {
        return $this->hasOne('App\Models\Postcode','id','suburb_id');
        
    }
    public function userPostcode()
    {
        return $this->hasOne('App\Models\UserPostcode','suburb_id','suburb_id')->where('status',1);
        
    }

   
}
