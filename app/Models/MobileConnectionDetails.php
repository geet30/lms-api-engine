<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CommonApi\{BasicCrudMethods};


class MobileConnectionDetails extends Model
{
    use HasFactory, BasicCrudMethods;
    protected $table = 'sale_product_mobile_connection_details';
    protected $fillable = ['mobile_connection_id', 'connection_request_type', 'current_provider', 'connection_account_no', 'connection_phone', 'conn_is_lease', 'conn_renew_lease_start_date'];
}
