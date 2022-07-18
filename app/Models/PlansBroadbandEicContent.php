<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlansBroadbandEicContent extends Model
{
    use HasFactory;

    protected $fillable = ['plan_id', 'type', 'content', 'status'];
}
