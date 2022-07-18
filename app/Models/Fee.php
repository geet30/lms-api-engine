<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Fee extends Model
{
	protected $table = 'fees';
    use HasFactory;

     protected $fillable = ['fee_name','order'] ;
}
