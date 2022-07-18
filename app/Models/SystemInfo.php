<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\SystemInfo\ { Methods };

/**
* SystemInfo Model.
* Author: Sandeep Bangarh
*/

class SystemInfo extends Model
{
    use Methods;

    protected $table = 'system_info';

    protected $fillable = [
        'lead_id', 'browser','platform','device','user_agent','screen_resolution','ip_address','latitude','longitude'
    ];
}
