<?php

namespace App\Traits\Customer;

use App\Rules\Plan\Energy\EnergyType;
use App\Rules\Plan\Energy\PropertyType;
use App\Rules\Plan\Energy\ValidateMoveinDate;
use App\Rules\Plan\Energy\ValidateElecDistributor;
use App\Rules\Plan\Energy\ValidateGasDistributor;
use App\Rules\Plan\Energy\ValidatePostCode;
/**
 * Customer Validation model.
 * Author: Sandeep Bangarh
 */

trait Validation
{

    

    static function customerRules () {
        $rules = [
            'first_name' => 'required|min:2|max:50',
            'email' => 'required|email'
        ];
        return array_merge($rules, self::phoneRules());
    }

    static function customerMessages () {
         $messages = [
            'email.required' => 'Email is required',
            'email.email' => 'Enter valid Email address',
            'first_name.required' => 'First name is required',
            'first_name.min' => 'Minimum two characters are required for first name',
            'first_name.max' => 'Maximum fifty characters are allowed for first name',
            'last_name.required' => 'Last name is required',
            'last_name.min' => 'Minimum two characters are required for last name',
            'last_name.max' => 'Maximum fifty characters are allowed for last name'
        ];

        return array_merge($messages, self::phoneMessages());
    }

    static function phoneRules () {
        return [
            'visit_id' => 'required',
            'phone' => 'required|numeric|phone_custom|phone_length'
        ];
    }

    static function phoneMessages () {
        return [
            'visit_id.required' => 'Visit id is required',
            'phone.required' => 'Phone is required',
            'phone.numeric' => 'Enter valid Mobile Number',
            'phone.phone_custom' => 'Mobile number must starts with 04',
            'phone.phone_length' => 'Mobile Number must be 10 digits long',
        ];
    }



    static function CreateMoveinCustomerRule($request){

        $moveing = [];
        $identificationRule= [];
        $identificationRuleType = [];
      
        $rules = [
            'first_name' => 'required|min:2|max:50',
            'email' => 'required|email',
            'post_code' => ['required', new ValidatePostCode($request)],
            'energy_type' => ['required', new EnergyType($request)],
            'property_type' => 'required|in:1, 2',
            'moving_house' => 'required|boolean',
            'life_support' => 'required|boolean',
            'life_support_value' => 'required_if:life_support,==,1|string',
            'life_support_energy_type' => 'required_if:life_support,==,1|in:1,2,3',
            'credit_score' => 'numeric',
            
            

        ];
      
        if ($request->has('moving_house') && $request->get('moving_house') == 1) {
            $moveing['moving_date'] = ['bail', 'required', 'date_format:d/m/Y', 'after:today', new ValidateMoveinDate($request)];
        }
        if ($request->has('identification_type')) {
            $identification = ['passport','Drivers Licence','medicare card','australian passport'];
            if(!in_array(strtolower($request->identification_type),$identification)){
               
                $identificationRule['identification_type'] = ['required'];
                
            }else{

                if ($request->has('identification_type') && strtolower($request->identity_type) == 'drivers licence') {
                    $identificationRuleType['licence_state'] = 'required';
                    $identificationRuleType['licence_number'] = 'required';
                    $identificationRuleType['licence_expiry_date'] = 'required|date_format:d/m/Y';

                }elseif($request->has('identification_type') && (strtolower($request->identity_type) == 'australian passport')){
                    $identificationRuleType['passport_number'] = 'required';
                    $identificationRuleType['expiry_date'] = 'required|date_format:d/m/Y';
                    
                }elseif( strtolower($request->identification_type) == 'foreign passport'){
                    $identificationRuleType['foreign_country_name'] = 'required';
                    $identificationRuleType['foreign_country_code'] = 'required';
                    $identificationRuleType['foreign_passport_number'] = 'required';
                    $identificationRuleType['foreign_passport_expiry_date'] = 'required|date_format:d/m/Y';

                }elseif( strtolower($request->identification_type) == 'medicare card'){
                    $identificationRuleType['medicare_number'] = 'required|numeric|digits:10';
                    $identificationRuleType['middle_name_on_card'] = 'required';
                    $identificationRuleType['medicare_card_expiry_date'] = 'required|date_format:d/m/Y';
                }
            }
        }
      
        $data= array_merge($rules,$moveing,$identificationRule,$identificationRuleType);
       
        return $data;
        
       
    }


    static function  createMoveInMessage($request){

          $message['licence_state.required'] = 'licence_state parameter is required OR parameter you are passing for licence_state may have wrong title';
    
            $message['licence_number.required'] = 'licence_number parameter is required OR parameter you are passing for licence_number may have wrong title';


            $message['expiry_date.required'] = 'expiry_date parameter is required OR parameter you are passing for expiry_date may have wrong title';

            $message['expiry_date.date_format']='expiry_date must be DD/MM/YYYY format';

            return $message;
    }
}