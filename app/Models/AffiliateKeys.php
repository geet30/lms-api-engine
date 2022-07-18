<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Keys\ { Methods, Relationship };

/**
* AffiliateKeys Model.
* Author: Sandeep Bangarh
*/

class AffiliateKeys extends Model
{
    use Methods, Relationship;

    protected $table = 'affiliate_keys';
    protected $fillable = ['user_id', 'name', 'api_key', 'page_url', 'origin_url', 'status', 'rc_code', 'is_default', 'deleted_at', 'created_at', 'updated_at'];
}
