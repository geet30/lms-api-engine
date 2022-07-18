<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DistributorPostCode;


class DmoVdoPrice extends Model
{
    use HasFactory;
    protected $table = 'dmo_vdo_price';
    protected $fillable = ['distributor_id','property_type','tariff_type','tariff_name','offer_type','annual_price','peak_only','peak_offpeak_peak','peak_offpeak_offpeak','peak_shoulder_peak','peak_shoulder_offpeak','peak_shoulder_shoulder','peak_shoulder_1_2_peak','peak_shoulder_1_2_offpeak','peak_shoulder_1_2_shoulder_1','peak_shoulder_1_2_shoulder_2','control_load_1','control_load_2','control_load_1_2_1','control_load_1_2_2','annual_usage'];

}
