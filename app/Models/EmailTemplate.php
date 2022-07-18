<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = ['title','subject','description','status','type','from_name','from_email','to_email','cc_email','bcc_email','enable_notification_status','send_attachment_or_not'];
}