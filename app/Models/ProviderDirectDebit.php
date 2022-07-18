<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ProviderDirectDebit extends Model
{

    public function providerContentCheckbox()
    {
        return $this->hasMany('App\Models\ProviderContentCheckbox', 'provider_content_id', 'id');
    }
}
