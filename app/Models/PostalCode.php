<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostalCode extends Model
{
    protected $table = 'postcodes';
	protected $fillable = ['postcode', 'suburb', 'state', 'latitude', 'longitude'];

}
