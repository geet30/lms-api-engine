<?php

namespace App\Rules\Plan\Energy;

use Illuminate\Contracts\Validation\Rule;
use App\Models\{PlansBroadband};

class EnergyType implements Rule
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

        if($value == 'gas'){
            return true;
        }elseif($value == 'electricity'){
            return true;
        }elseif($value == 'electricitygas'){

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
        return 'Please enter valid Energy type.';
    }
}
