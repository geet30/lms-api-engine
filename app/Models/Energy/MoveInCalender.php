<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class MoveInCalender extends Model
{

    protected $table = 'move_in_calender';
    protected $fillable = ['year', 'date', 'holiday_type', 'state', 'holiday_title', 'holiday_content', 'created_by', 'updated_by', 'status', 'created_at', 'updated_at'];
}
