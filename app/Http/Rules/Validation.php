<?php

namespace App\Http\Rules;

class Validation
{

	/**
	 * Validate Phone Numbers
	 * Updated by: Sandeep Bangarh
	 **/
	public function validatePhoneCustom($attribute, $value)
	{
		if (in_array($value, config('mobile_numbers.temporary'))) {
			return true;
		}

		if (substr($value, 0, 2) == 04) {
			return true;
		} elseif (substr($value, 0, 3) == 614) {
			return true;
		}

		return false;
	}

	/**
	 * Validate Phone Numbers Length
	 * Updated by: Sandeep Bangarh
	 **/
	public function validatePhoneLength($attribute, $value)
	{
		if (in_array($value, config('mobile_numbers.temporary'))) {
			return true;
		}

		if (strlen($value) < 10 || strlen($value) > 10) {
			return false;
		}

		return true;
	}

	/**
	 * Validate Energy type
	 * Updated by: Sandeep Bangarh
	 **/
	public function validateEnergyType($attribute, $value)
    {
        $arr = ['gas', 'electricity', 'electricitygas'];

        if (in_array($value, $arr)) {
            return true;
        }
		return false;
    }

	public function validateEqualTo($attribute, $value, $parameters) {
		if($parameters[0] == $value){
				$response = true;
			}else{
				$response = false;
			}
			return $response;
	
	  }

	
}
