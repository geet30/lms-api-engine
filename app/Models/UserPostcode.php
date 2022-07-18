<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPostcode extends Model
{
    use HasFactory;
    protected $guarded = ['id']; 
    public function postcode()
    {
        return $this->belongsTo('App\Models\Postcode');
    }

   
}
