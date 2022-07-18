<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlansBroadbandContentCheckbox extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'plan_id', 'required', 'validation_message', 'content', 'module_type', 'status'];
}
