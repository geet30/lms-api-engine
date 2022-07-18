<?php

namespace App\Traits\Otp;

use Illuminate\Validation\Rule;
use App\Models\Lead;

/**
 * Otp Validation model.
 * Author: Sandeep Bangarh
 */

trait Validation
{
    protected static $lead;

    static function rules () {
        if(request('otp_from')){
            return [
                'visit_id' => 'required',
                'otp' => 'required|numeric'
            ];
        } else {
            return [
                'visit_id' => 'required',
                'otp' => 'required|numeric',
                'plan_id'=>'required|array'
            ];
        }
    }

    static function messages () {
        return [
            'visit_id.required' => 'Visit id is required',
            'otp.required' => 'OTP is required',
            'otp.numeric' => 'OTP must be numeric',
            'plan_id.required'=>'Plan ids is required',
            'plan_id.array'=>'Plan id is must be of type array'
        ];
    }

    static function sendOtpRules () {
        $serviceId = request()->header('ServiceId');
        return [
            'visit_id' => 'required',
            'variant_id' => Rule::requiredIf($serviceId == 2 && self::$lead->plan_type == 2 && !self::$lead->variant_id),
            'handset_id'=> Rule::requiredIf($serviceId == 2 && self::$lead->plan_type == 2 && !self::$lead->handset_id)
        ];
    }

    static function sendOtpMessages () {
        return [
            'visit_id.required' => 'Visit id is required',
            'variant_id.required' => 'Variant id is required',
            'variant_id.numeric' => 'Variant id must be numeric',
            'handset_id.required' => 'Handset id is required',
            'handset_id.numeric' => 'Handset id must be numeric'
        ];
    }

    static function setLead ($request) {
        $leadId = decryptGdprData($request->visit_id);
        $service = Lead::getService();
        $columns = ['visitors.first_name', 'visitors.last_name', 'visitors.middle_name', 'visitors.phone', 'visitors.alternate_phone', 'visitors.dob', 'visitors.email', 'leads.status', 'leads.visitor_id', 'leads.sale_created', 'visitor_addresses.address','visitor_addresses.state', 'sale_products_'.$service.'.plan_id','sale_products_'.$service.'.provider_id','sale_products_'.$service.'.id as product_id','billing_address_id','delivery_address_id','billing_preference','delivery_preference'];
        if ($service == 'mobile') {
            array_push($columns, 'sale_products_'.$service.'.cost');
            array_push($columns, 'plan_type');
            array_push($columns, 'handset_id');
            array_push($columns, 'variant_id');
        }
        self::$lead = Lead::getFirstLead(
            ['leads.lead_id' => $leadId],
            $columns,
            true,
            true,
            true,
            null,
            null,
            true
        );
        
        if ($service == 'mobile') {
            $request->merge(['handset_id' => self::$lead->handset_id,'variant_id' => self::$lead->variant_id]);
        }
    }

    static function getLead () {
        return self::$lead;
    }
}