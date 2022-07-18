<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorDebitInfo extends Model
{
    use HasFactory;
    protected $table = 'visitor_debit_info';

	protected $fillable = ['lead_id','provider_id','auth_key_used', 'timestamp_used', 'name_on_card','first_six','last_four','exp_month','exp_year','cvv','card_type','forter_init_response','k_hash','reference_number','token','token_hMAC','is_valid','is_cvv_valid','type','service_type','cvv_included','tokenize_data'];
}
