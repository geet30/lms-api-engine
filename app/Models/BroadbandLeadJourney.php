<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BroadbandLeadJourney extends Model
{

    protected $table = 'lead_journey_data_broadband';

    protected $fillable = ['lead_id','connection_type','technology_type','address','movein_type','movein_date','current_provider','no_of_user','use_of_internet','streaming_type','spend_crr_bill'];
}