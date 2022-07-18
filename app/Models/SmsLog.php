<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
* SmsLog Model.
* Author: Sandeep Bangarh
*/

class SmsLog extends Model
{
    protected $table = 'sms_logs';
    protected $fillable = [
        'user_id','lead_id','service_id','template_name','sms_status','response','request','message','phone','sender_id','message_source'
    ];
}
