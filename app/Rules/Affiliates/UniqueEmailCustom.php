<?php

namespace App\Rules\Affiliates;

use Illuminate\Contracts\Validation\Rule;
use App\Models\{
    User
};

class UniqueEmailCustom implements Rule
{
    protected $request_array=[];

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request_array = $request;
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
        $user = User::where('email',encryptGdprData($this->request_array['email']))->select('id')->first();
        if($user){ 
            if(isset($this->request_array['id'])){
                $id = decryptGdprData($this->request_array['id']);

                if($user->id==$id){
                    return true; 
                }else{
                    return false; 
                }
            }

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
        return trans('affiliates.email_unique');
    }
}
