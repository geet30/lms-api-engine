<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Concession\Methods;

class VisitorConcessionDetail extends Model
{
    use  HasFactory,Methods;
    protected $table='visitor_concession_details';
    protected $fillable = ['visitor_id','energy_concession','concession_type','concession_code','card_start_date','card_expiry_date','card_number'];
    
  
}
