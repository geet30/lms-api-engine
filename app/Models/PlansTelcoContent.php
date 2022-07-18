<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlansTelcoContent extends Model
{
    use HasFactory;

    protected $fillable = ['plan_id', 'title', 'description', 'slug', 'service_id'];
    // protected $appends = ['enc_id'];

    public function getIdAttribute($value)
    {
        return encryptGdprData($value);
    }
}
