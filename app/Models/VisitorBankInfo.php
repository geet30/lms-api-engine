<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorBankInfo extends Model
{
    use HasFactory; 

	protected $fillable = ['id','lead_id','provider_id','bank_name','branch_name','name_on_account','bsb','account_number'];
}
