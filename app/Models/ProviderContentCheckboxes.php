<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderContentCheckboxes extends Model
{

	protected $table = 'provider_content_checkboxes';
	
    protected $fillable = ['checkbox_required', 'validation_message', 'content', 'type', 'status', 'provider_id','term_id'];
}
