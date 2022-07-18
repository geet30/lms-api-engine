<?php

namespace App\Http\Requests\Energy;

class DemandRequest
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

		$rules = array();

        $rules = [
            'distributor_id' => 'required',
        ];

		
		return $rules;
	}

	public function messages()
	{
		$message = array();
		$message = [
			'distributor_id.required'=>'Distributor Id is required',  
			

		];
		return $message;
	}

}
