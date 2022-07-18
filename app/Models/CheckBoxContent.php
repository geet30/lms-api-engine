<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckBoxContent extends Model
{
    use HasFactory;
    protected $table = 'check_box_content';
    protected $fillable = ['type', 'type_id', 'status', 'validation_message', 'content', 'module_type', 'save_checkbox_status'];
}
