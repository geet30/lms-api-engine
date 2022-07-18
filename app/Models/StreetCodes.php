<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CommonApi\BasicCrudMethods;

/**
 * SolarType Model.
 * Author: Sandeep Bangarh
 */

class StreetCodes extends Model
{

    use BasicCrudMethods;

    protected $table = 'master_street_codes';
}
