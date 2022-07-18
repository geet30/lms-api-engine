<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ProviderContent extends Model
{
    use HasFactory;

    public function checkbox()
    {
        return $this->hasMany('App\Models\ProviderContentCheckboxes', 'provider_content_id', 'id');
    }
   
}
