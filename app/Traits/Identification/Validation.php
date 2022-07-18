<?php

namespace App\Traits\Identification;

use Illuminate\Validation\Rule;

/**
 * Identification Validation model.
 * Author: Sandeep Bangarh
 */

trait Validation
{

    static function rulesAndMessages () {
        $request = request();
        $rules = [
            'visit_id' => 'required',
            'identification_details' => 'required|array'
        ];
        $messages = static::messages();
        
        foreach($request->identification_details as $key => $idntifyObj) {
            if ($idntifyObj['identification_required'] == 1) {
                foreach(static::$sections as $name => $title) {
                    if ($idntifyObj['identification_type'] == $title) {
                        foreach(static::${$name.'Fields'} as $field) {
                            $rules['identification_details.'.$key.'.'.$field] = 'required';
                            $messages['identification_details.'.$key.'.'.$field.'.required'] = str_replace('_', ' ', $field).' is required';
                        }
                    }
                }
            }
        }
        return ['rules' => $rules, 'messages' => $messages];
    }

    static function messages () {
        return [
            'visit_id.required' => 'Visit id is required',
            'identification_details.required' => 'identification detail is required',
            'identification_details.array' => 'identification detail must be array'
        ];
    }
}