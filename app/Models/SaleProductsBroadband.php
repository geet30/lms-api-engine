<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Plan\ApplyNow;
use App\Traits\CommonApi\BasicCrudMethods;
use App\Traits\Broadband\BasicCrudMethods as BroadbandBasic;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Product\{Methods, Query, Relationships, Redis};

class SaleProductsBroadband extends Model
{
    use SoftDeletes, ApplyNow, Methods, Query, Relationships, Redis, BasicCrudMethods, BroadbandBasic;

    protected $table = 'sale_products_broadband';
    protected $fillable = ['lead_id', 'service_id', 'product_type', 'provider_id', 'plan_id', 'cost_type', 'cost', 'reference_no', 'is_moving', 'moving_at', 'sale_created_at','is_duplicate'];
}
