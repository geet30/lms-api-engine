<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class LeadJourneyDataBroadband extends Model
{
    use HasFactory;    
    protected $table = 'lead_journey_data_broadband';
    protected $fillable = ['lead_id', 'connection_type', 'technology_type', 'address', 'movein_type', 'movein_date'];
}
