<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Marketing\ { Methods };

class Marketing extends Model
{
    use Methods;

    protected $table = 'marketing';

    protected $fillable = [
        'lead_id', 'rc','cui', 'utm_source','utm_medium','utm_campaign','utm_term','utm_rm', 'utm_rm_source', 'utm_rm_date', 'utm_content','gclid','fbclid','customer_user_id','msclkid'
    ];
}
