<?php

namespace App\Rules\Plan\Broadband;

use Illuminate\Contracts\Validation\Rule;
use App\Models\{PlansBroadband};

class UniqueBroadbandPlan implements Rule
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
        if($this->requestData['action'] =='add'){
            $planExists = PlansBroadband::where('name',$value)->first();
        }
        else if($this->requestData['action'] =='edit'){
            $planExists = PlansBroadband::where('name',$value)->where('id','!=',decryptGdprData($this->requestData['plan_id']))->first(); 
        }
        if(!empty($planExists)){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Plan Name already exists, please choose another Plan Name.';
    }
}
