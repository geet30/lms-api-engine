<?php

namespace App\Rules\Affiliates;

use Illuminate\Contracts\Validation\Rule;
use App\Models\{
    Userip
};

class UniqueIp implements Rule
{
    protected $requestArray=[];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->requestArray = $request;
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
        $user = Userip::where('ips',encryptGdprData($this->requestArray['ips']))->select('id')->first();
        if($user){ 
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
        return trans('affiliates.ipunique');
    }
}
