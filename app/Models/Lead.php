<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Lead\{Relationship, Methods, Redis, QueryMethods, Dialler, Order};
use App\Traits\Plan\Validation;

use App\Traits\Plan\Energy\{SaveJourney};

/**
 * Lead Model.
 * Author: Sandeep Bangarh
 */

class Lead extends Model
{
    use Methods, Relationship, Redis, QueryMethods, SaveJourney, Validation, Dialler, Order;

    protected $primaryKey = 'lead_id';

    protected $fillable = ['visitor_id', 'affiliate_id', 'sub_affiliate_id', 'sale_source_id', 'api_key_id', 'post_code', 'address', 'affiliate_portal_type', 'referal_code', 'referal_title', 'is_duplicate', 'visitor_source', 'sale_source', 'sale_comment', 'sale_submission_attempt', 'connection_address_id', 'billing_preference', 'delivery_preference', 'billing_address_id', 'billing_po_box_id', 'status', 'sale_created', 'plan_type', 'connection_type', 'current_provider', 'data_usage_min', 'data_usage_max', 'data_cost_min', 'data_cost_max','delivery_instruction_details','australia_resident_status','delivery_date','connection_address_id','delivery_address_id','billing_address_id'];
}
