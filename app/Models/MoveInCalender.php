<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CommonApi\BasicCrudMethods;

class MoveInCalender extends Model
{
    use BasicCrudMethods;
    protected $table = 'move_in_calender';
    protected $fillable = ['year', 'date', 'holiday_type', 'state', 'holiday_title', 'holiday_content', 'created_by', 'updated_by', 'status', 'created_at', 'updated_at'];
}
