<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\PlansEnergy\{
    PlanRateCrud,Accessor
};
class PlanDmo extends Model
{
    use HasFactory;
    protected $table = "dmo_content";
    protected $fillable = ['plan_rate_id','type','variant','dmo_vdo_content','dmo_content_status','lowest_annual_cost','without_conditional','without_conditional_value','with_conditional','with_conditional_value'];
}
