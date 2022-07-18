<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReconSale extends Model
{
    protected $fillable = ['lead_id','sale_reference_no','recon_reference_no','affiliate_id','parent_id','file_name','lead_status','energy_type','recon_status','last_updated_columns','last_updated_by','sale_created'];

    protected $dates = ['sale_created'];
}