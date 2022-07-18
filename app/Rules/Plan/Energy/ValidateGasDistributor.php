<?php

namespace App\Rules\Plan\Energy;

use Illuminate\Contracts\Validation\Rule;

use App\Models\Distributor;

use Carbon\Carbon;

class ValidateGasDistributor implements Rule
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
        $request  =  $this->requestData;
        $postCode = $request->post_code;
        $postCode = explode(',',$request->post_code);
        $postCode = trim($postCode['0']);

        $distributorId = $value;
        if($distributorId){
            $data = Distributor::where('id',$distributorId)->where('energy_type',2)->where('status',1)->with(
                ['distrbutorPostcode'=>function($q)use($postCode){
                 $q->where('post_code',$postCode);
            }])->pluck('id');

            if($data){
                if(in_array($distributorId,$data->toArray())){
                    return true;
                }
                return false;
            }else{
                return false;
            }
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
        return 'Please enter valid Gas distributor_id.';
    }
}
