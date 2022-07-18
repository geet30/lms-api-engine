<?php
namespace App\Http\Requests\Mobile;


class PlanInfoRequest 
{
 
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
        /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(){
		$rules = array();
        $rules = [
            'plan_id'=>'required',
        ];
  
        return $rules;
    }
        
    public function messages(){
		$message = array();
		$message['plan_id.required']='plan id is required';
		return $message;
    }
}
