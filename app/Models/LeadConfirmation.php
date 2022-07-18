<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* LeadConfirmation Model.
* Author: Sandeep Bangarh
*/

class LeadConfirmation extends Model
{
    protected $table = 'lead_confirmation';
    protected $fillable = [
        'lead_id','product_id','sms_sent','email_sent','type'
    ];
}
