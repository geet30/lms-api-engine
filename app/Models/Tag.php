<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    protected $fillable =['name','is_highlighted','is_one_in_state','is_top_of_list','set_for_all_plans','rank','status','is_deleted'];
}
