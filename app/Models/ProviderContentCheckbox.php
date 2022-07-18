<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderContentCheckbox extends Model
{
    use HasFactory;
    protected $table = 'provider_content_checkboxes';

    protected $fillable = ['provider_content_id','checkbox_required','validation_message','content','status','type','deleted_at'];
}
