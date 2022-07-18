<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Identification\ { Methods, Validation };

class VisitorIdentification extends Model
{
    use Methods, Validation; 

	protected $fillable = ['lead_id','identification_type','licence_state_code', 'licence_number', 'licence_expiry_date','passport_number','passport_expiry_date','foreign_passport_number','foreign_passport_expiry_date','medicare_number','reference_number','card_color','medicare_card_expiry_date','foreign_country_name','foreign_country_code','card_middle_name','identification_option','identification_content'];
}
