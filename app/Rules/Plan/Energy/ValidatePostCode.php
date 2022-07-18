<?php

namespace App\Rules\Plan\Energy;

use Illuminate\Contracts\Validation\Rule;
use App\Models\PostalCode;

class ValidatePostCode implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $requestData=[];
    public function __construct($request){
        $this->requestData= $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    { 
        $postCode = explode(',',$value);
       

            if(count($postCode) == 3){
                $validPostcode=PostalCode::where('postcode',trim($postCode[0]))->where('suburb',trim($postCode[1]))->where('state',trim($postCode[2]))->first();

                if($validPostcode){
                    return true;
                }
                    return false;
            }
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please enter valid Post code.';
    }
}
