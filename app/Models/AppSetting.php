<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Settings\ { Methods };

class AppSetting extends Model
{
    use Methods;
    
    protected $table = 'app_settings';
    protected $fillable = ['id', 'type', 'label', 'content', 'attributes', 'status'];
}
