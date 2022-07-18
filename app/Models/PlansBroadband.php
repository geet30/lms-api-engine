<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Broadband\{BasicCrudMethods, Relationship};


class PlansBroadband extends Model
{
    use BasicCrudMethods, Relationship;
    protected $fillable = array ('provider_id','name','contract_id','connection_type','satellite_inclusion','inclusion','connection_type_info','internet_speed','internet_speed_info','plan_cost_type_id','plan_cost','is_boyo_modem','plan_cost_info','plan_cost_description','nbn_key','nbn_key_url','download_speed','upload_speed','typical_peak_time_download_speed','data_limit','speed_description','additional_plan_information','plan_script','total_data_allowance','off_peak_data','peak_data','special_offer_status','special_cost_id','special_offer_price','special_offer','critical_info_type','critical_info_url','critical_info_summary','status','remarketing_allow','version','billing_preference');

}
