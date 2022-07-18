<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Otp\ { Methods, Validation };

/**
* LeadOtp Model.
* Author: Sandeep Bangarh
*/

class LeadOtp extends Model
{
    use Methods, Validation;

    protected $fillable = ['lead_id', 'otp', 'expires_at'];

    protected $dates = ['expires_at'];
}
