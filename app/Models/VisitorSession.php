<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitorSession extends Model
{
    protected $fillable = [
     	'sale_id','visitor_token','expire_on','expire_status','created_at','updated_at'
     ];
}
