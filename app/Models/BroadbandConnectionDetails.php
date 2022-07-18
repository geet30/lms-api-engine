<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\CommonApi\{BasicCrudMethods};


class BroadbandConnectionDetails extends Model
{
    use HasFactory, BasicCrudMethods;
    protected $table = 'sale_product_broadband_connection_details';
    protected $fillable = ['is_provider_account', 'provider_account', 'is_phone_number', 'home_number', 'current_account', 'transfer_service', 'broadband_connection_id'];
}
