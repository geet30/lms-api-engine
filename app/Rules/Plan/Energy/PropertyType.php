<?php

namespace App\Rules\Plan\Energy;

use Illuminate\Contracts\Validation\Rule;

class PropertyType implements Rule
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
        if($value == 'business'){
            return true;
        }elseif($value == 'residential'){
            return true;
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
        return 'Please enter valid property type.';
    }
}
