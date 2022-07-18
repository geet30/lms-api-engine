<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Lead\{ Relationship, Methods };
class LeadJourneyDataMobile extends Model
{
    use HasFactory,Methods, Relationship;


    
    protected $table = 'lead_journey_data_mobile';

    protected $fillable = ['lead_id', 'connection_type', 'current_provider', 'contract', 'plan_cost_min', 'plan_cost_max', 'data_usage_min','plan_type','sim_type'];

  



    
}
