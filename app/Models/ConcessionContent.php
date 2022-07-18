<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Concession\Methods;


class ConcessionContent extends Model
{
    use Methods;
    protected $table = 'concession_content';
    protected $fillable = ['id', 'provider_id', 'state_id', 'type','content', 'status', 'created_at', 'updated_at'];
}
