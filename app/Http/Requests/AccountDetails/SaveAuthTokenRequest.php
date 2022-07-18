<?php

namespace App\Http\Requests\AccountDetails;



class SaveAuthTokenRequest
{

	protected $request;

	function __construct($request)
	{
		$this->request = $request;
	}


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
	public function rules()
	{
		// echo "<pre>";print_r($this->request);die;
		$rules = array();
		if($this->request->type == 2){
			$rules = ['bank_name' => "required"];
			$rules = ['branch_name' => "required"];
			$rules = ['name_on_account' => "required"];
			$rules = ['account_no' => "required|numeric|digits_between:6,30"];
			$rules = ['bsb_no' => "required|numeric|digits:6"];
		}else{

			$rules = ['name_on_card' => "required"];
		}
		
		
		return $rules;
	}

	public function messages()
	{
		$message = array();
        $message['name_on_card.required']='Name on Card is required';   
		$message['name_on_card.regex']='Name may only contain letters and spaces.'; 
		$message['bank_name.required']='Bank Name is required';   
		$message['branch_name.required']='Branch Name is required';     
		$message['name_on_account.required']='Name on Account is required';   
		$message['account_no.required']='Account No is required';  
		$message['account_no.numeric']='Account No should be numeric';  
		$message['account_no.digits_between']='Account Number should be between 6 and 30';  
		$message['bsb_no.required']='BSB No. is required';  
		$message['bsb_no.numeric']='BSB No. should be numeric';  
		$message['bsb_no.digits_between']='BSB No. should be between 6 and 30';   
        return $message;
	}


}
