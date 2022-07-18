<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\PlansEnergy\{
    PlanRateCrud,Accessor
};
class EnergyContentAttribute extends Model
{
    use HasFactory;
}
