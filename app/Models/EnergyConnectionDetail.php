<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Connection\ { EnergyMethods };

/**
* LeadOtp Model.
* Author: Sandeep Bangarh
*/

class EnergyConnectionDetail extends Model
{
    use EnergyMethods;

    protected $table = 'sale_product_energy_connection_details';
    protected $fillable = ['connection_post_code', 'connection_suburb', 'connection_state','connection_street_number','connection_street_name','manually_connection_address'];
}
