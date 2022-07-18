<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SaleBusinessDetail extends Model
{
    use SoftDeletes;
    protected $table = 'sale_business_details';
    
	
    protected $fillable = [
     	'lead_id','business_name','business_abn','business_postcode','year_incorporated','business_employee','business_representative','director_title','director_first_name','director_middle_name','director_last_name','director_email','director_phone','director_dob'
    ];


}
