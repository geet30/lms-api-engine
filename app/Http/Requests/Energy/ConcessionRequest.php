<?php

namespace App\Http\Requests\Energy;

class ConcessionRequest
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
        $rules = [];
        $rules['concession_type']  = 'required';
        $rules['concession_allowed']  = 'required';
		$rules['visit_id'] = 'required';
        if(($this->request->has('concession_type') && $this->request->concession_type != 1) && ($this->request->has('concession_allowed') && $this->request->concession_allowed == 1)){
            $rules['card_number']      = 'required|min:1|max:16';
            $rules['card_issue_date']  = 'required|date_format:d/m/Y|before:yesterday';
            $rules['card_expiry_date'] = 'required|date_format:d/m/Y|after:card_start_date|after:yesterday';
        } 
       
        return $rules;
	}

	public function messages()
	{
		$message = [
			'card_number.required'         => 'Card number is required',
			'card_number.alpha_numeric'    => 'The card number must only have numbers and alphabets',
			'card_start_date.required'     => 'Card start date is required',
			'card_start_date.date_format'  => 'Card start date format will be (dd/mm/yyyy)',
			'card_expiry_date.required'    => 'Card expiry date is required',
			'card_expiry_date.date_format' => 'Card expiry date format will be (dd/mm/yyyy)',
			'card_expiry_date.after'       => 'Expiry must be a future date.',
			'card_start_date.before'       => 'Card issue date must be past date.',
			

		];
		return $message;
	}

}
