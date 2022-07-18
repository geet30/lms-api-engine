<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorInformationEnergy extends Model
{
    use HasFactory;

    protected $table = 'visitor_informations_energy';
    protected $fillable = ['nmi_number','dpi_mirn_number','nmi_skip','mirn_skip','meter_number_e','meter_number_g','electricity_network_code','gas_network_code','tariff_list','tariff_type','electricity_code','gas_code','meter_hazard','dog_code','site_access_electricity','site_access_gas','joint_acc_holder_title','joint_acc_holder_first_name','joint_acc_holder_last_name','joint_acc_holder_email','joint_acc_holder_phone_no','joint_acc_holder_home_phone_no','joint_acc_holder_office_phone_no','visitor_id'];
    
    static function saveJointAccountDetails($requestData,$visitorId){

        $saveData['joint_acc_holder_title'] = $requestData->joint_acc_holder_title;
        $saveData['joint_acc_holder_first_name'] = $requestData->joint_acc_holder_first_name;
        $saveData['joint_acc_holder_last_name'] = $requestData->joint_acc_holder_last_name;
        $saveData['joint_acc_holder_email'] = $requestData->joint_acc_holder_email;
        $saveData['joint_acc_holder_phone_no'] = $requestData->joint_acc_holder_phone_no;
        $saveData['joint_acc_holder_home_phone_no'] = $requestData->joint_acc_holder_home_phone_no;
        $saveData['joint_acc_holder_office_phone_no'] = $requestData->joint_acc_holder_office_phone_no;
        $saveData['visitor_id'] = $visitorId;

        return self::Create($saveData);
    }
}
