<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LifeSupport\ { Methods };

/**
* LifeSupportEquipment Model.
* Author: Sandeep Bangarh
*/

class LifeSupportEquipment extends Model
{
    use Methods;

    protected $table = 'life_support_equipments';
    protected $fillable = ['title', 'status'];
}
