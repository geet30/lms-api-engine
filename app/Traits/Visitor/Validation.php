<?php

namespace App\Traits\Visitor;

use Illuminate\Validation\Rule;

/**
 * Visitor Validation model.
 * Author: Sandeep Bangarh
 */

trait Validation
{

    static function rules ($rulesFor='create') {
        $request = request();
        $serviceId = (int) $request->header('ServiceId');
        if ($rulesFor == 'update') {
            return [
                'visit_id' => 'required',
                'percentage' => 'required',
                'step_name' => Rule::requiredIf(!$request->has('screen_no')),
                'screen_name' => Rule::requiredIf(!$request->has('screen_no')),
                'screen_no' => Rule::requiredIf(!$request->has('step_name'))
            ];
        }
        $rule = 'required|integer|exists:visitor_addresses,id';
        //if ($serviceId==3) return ['connection_address_id' => $rule ];
   
        return [];
      }

    static function messages ($rulesFor='create') {
        if ($rulesFor == 'update') {
            return [
                'visit_id.required' => 'Visit id is required',
                'percentage.required' => 'percentage is required',
                'step_name.required' => 'Step name is required',
                'screen_name.required' => 'Screen name is required',
            ];
        }

        return ['connection_address_id.required' => 'Connection address id is required','connection_address_id.exists' => 'Connection address id is not exist','connection_address_id.integer' => 'Connection address id must be integer'];
    }
}