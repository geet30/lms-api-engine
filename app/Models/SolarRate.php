<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SolarRate extends Model
{
    use HasFactory;
    protected $fillable = [
        'plan_id', 'solar_price','solar_description','is_show_frontend','status','deleted_at'];
}
