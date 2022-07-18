<?php

namespace App\Traits\Distributor;

/**
* Distributor validation model.
* Author: Sandeep Bangarh
*/

trait Validation
{
    static function rules () {
        return [
            'post_code' => 'required|numeric|exists:postcodes,postcode',
            'energy_type' => 'required|energy_type'
        ];
    }

    static function messages () {
        return [
            'post_code.required' => 'Post code is required',
            'post_code.numeric' => 'Post code should be numeric',
            'post_code.exists' => 'Post code does not exist',
            'energy_type.required' => 'Energy type is required.',
            'energy_type.energy_type' => 'Energy type value should be (gas or electricity or electricitygas)'
        ];
    }
}