<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CommonApi\{BasicCrudMethods};

/**
 * LeadEmploymentDetails Model.
 * Author: Harsimranjit Singh
 */

class LeadEmploymentDetails extends Model
{
    use BasicCrudMethods;
    protected $table = 'visitor_employment_details';

    protected $fillable = ['lead_id', 'occupation', 'occupation_started_month', 'user_have_cc', 'occupation_contact_number', 'employment_type', 'occupation_type', 'occupation_industry', 'occupation_status', 'occupation_started_yr', 'occupation_employer_name'];
}
