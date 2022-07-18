<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Otp extends Model
{
    use HasFactory;
    protected $table = 'lead_otps';
    protected $fillable = ['lead_id', 'otp', 'expires_at', 'status'];
}
