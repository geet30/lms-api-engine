<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\MobilePlan\{BasicCrudMethods};
use App\Traits\Broadband\BasicCrudMethods as BroadbandPlan;
use App\Traits\Plan\{Planlist};

class ConnectionType extends Model
{
    use HasFactory, BasicCrudMethods, BroadbandPlan;
    protected $table = 'connection_types';
    protected $fillable = ['service_id', 'name', 'connection_type_id', 'status', 'logo', 'is_deleted'];
    const SERVICE_MOBILE = 2;
    const SERVICE_BROADBAND = 3;
    const CONNECTION_TYPE_ID_ONE = 1;
    const CONNECTION_TYPE_ID_TWO = 2;
    const CONNECTION_TYPE_ID_THREE = 3;
    const ZERO = 0;
    const ONE = 1;
    const TEN = 10;
    const MAX_RANGE = 200;
    const CONNECTION_TYPE_ONE = 1;
    const CONNECTION_TYPE_TWO = 2;
    const CONNECTION_TYPE_THREE = 3;
    const PLAN_TYPE_SIM = 1;
    const PLAN_TYPE_MOBILE = 2;
    const CONNECTION_TYPE_ID_SIX = 6;
    const CONNECTION_TYPE_ID_FIVE = 5;
    const CONNECTION_TYPE_ID_SEVEN = 7;
}
