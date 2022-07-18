<?php

namespace App\Http\Requests\Energy;

use App\Rules\Plan\Energy\EnergyType;
use App\Rules\Plan\Energy\PropertyType;
use App\Rules\Plan\Energy\ValidateMoveinDate;
use App\Rules\Plan\Energy\ValidateElecDistributor;
use App\Rules\Plan\Energy\ValidateGasDistributor;
use App\Rules\Plan\Energy\ValidatePostCode;
use phpDocumentor\Reflection\Types\Boolean;

class CreateMoveInCustomer
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

		$service =  $this->request->header('serviceId');

		$rules = array();
		$request = $this->request;
		$identificationRule = [];
		$identificationRuleType = [];
		$rules = [
			'first_name' => 'required|min:2|max:50',
			'email' => 'required|email',
			'post_code' => ['required', new ValidatePostCode($request)],
			'energy_type' => ['required', new EnergyType($request)],
			'property_type' => 'required|in:1, 2',
			'moving_house' => 'required|boolean',
			'life_support' => 'required|boolean',
			'life_support_value' => 'required_if:life_support,==,1|string',
			'life_support_energy_type' => 'required_if:life_support,==,1|in:1,2,3',
			'credit_score' => 'numeric',
			//"identification_type" => "in:passport','Drivers Licence','medicare card','australian passport"

		];

		$result = $this->moveInRules($request);
		$indentificationData = $this->identificationRules($request);
		
		$rules = $indentificationData['rules'];
		// $rules = array_merge($rules, $result['rules'],$indentificationData['rules']);
		

		return $rules;
	}

	private function moveInRules($request)
	{
		$rules = [];
		// if ($request->has('moving_house') && $request->get('moving_house') == 1) {
		// 	$rules['moving_date'] = ['bail', 'required', 'date_format:d/m/Y', 'after:today', new ValidateMoveinDate($request)];
		// }
		return ['rules' => $rules];
	}

	private function identificationRules($request)
	{
		$rules = [];
		if ($request->has('identification_type')) {
			$identification = ['foreign passport', 'Drivers Licence', 'medicare card', 'australian passport'];
			// if (!in_array(strtolower($request->identification_type), $identification)) {
			//  	$rules['identification_type'] = 'numeric';
			// }

			// } else {
				
				if ($request->has('identification_type') && strtolower($request->identification_type) == 'drivers licence') {
					
					$rules['licence_state'] = 'required';
					$rules['licence_number'] = 'required';
					$rules['licence_expiry_date'] = 'required|date_format:d/m/Y';
				} elseif ($request->has('identification_type') && (strtolower($request->identification_type) == 'australian passport')) {
					$rules['passport_number'] = 'required';
					$rules['passport_expiry_date'] = 'required|date_format:d/m/Y';
				} elseif (strtolower($request->identification_type) == 'foreign passport') {
					$rules['foreign_country_name'] = 'required';
					$rules['foreign_country_code'] = 'required';
					$rules['foreign_passport_number'] = 'required';
					$rules['foreign_passport_expiry_date'] = 'required|date_format:d/m/Y';
				} elseif (strtolower($request->identification_type) == 'medicare card') {
					$rules['medicare_number'] = 'required|numeric|digits:10';
					$rules['middle_name_on_card'] = 'required';
					$rules['medicare_card_expiry_date'] = 'required|date_format:d/m/Y';
			    }
		}

		return ['rules' => $rules];
	}

	public function messages()
	{
		$message = array();
		$message = [
			'first_name.required'=> "First name is required",
			'email.required'=> "Email  is required",
			'post_code.required'=> "Post code  is required",
			'property_type.required' => "Please enter valid property type",
			'property_type.in' => "Please enter either 1 or 2",
			'solar_panel.required' => "Please enter solar panel",
			'life_support.required' => "Please enter life support",
			'life_support_value.required' => "Please enter life support value",
			'life_support_energy_type.required' => "Please enter life support",
			'life_support.required' => "Please enter life support",
			'moving_house.required' => "Please enter moving house",
			'life_support_energy_type.required_if' => "Please enter life support energyt type",
			'licence_state.required'=> 'licence_state is required',
			'licence_number.required'=> 'licence_number is required',
			'licence_number.required'=> 'licence_number is required',
			'licence_expiry_date.required'=> 'licence_expiry_date is required',
			//'licence_expiry_date.required'=> 'licence_expiry_date is required',
			'passport_number.required'=> 'passport_number is required',
			'passport_expiry_date.required'=> 'passport_expiry_date is required',
			'passport_expiry_date.required'=> 'passport_expiry_date is required',
			'foreign_country_name.required'=> 'foreign_country_name is required',
			'foreign_country_code.required'=> 'foreign_country_code is required',
			'foreign_passport_number.required'=> 'foreign_passport_number is required',
			'foreign_passport_expiry_date.required'=> 'foreign_passport_expiry_date is required',
			'medicare_number.required'=> 'medicare_number is required',
			'middle_name_on_card.required'=> 'middle_name_on_card is required',
			'medicare_card_expiry_date.required'=> 'medicare_card_expiry_date is required',
			'passport_number.required'=> 'passport_number is required',
		

		];
		return $message;
	}
}
