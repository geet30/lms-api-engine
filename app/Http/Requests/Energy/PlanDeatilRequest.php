<?php

namespace App\Http\Requests\Energy;

use App\Rules\Plan\Energy\EnergyType;
use App\Rules\Plan\Energy\PropertyType;
use App\Rules\Plan\Energy\ValidateMoveinDate;
use App\Rules\Plan\Energy\ValidateElecDistributor;
use App\Rules\Plan\Energy\ValidateGasDistributor;
use App\Rules\Plan\Energy\ValidatePostCode;
use phpDocumentor\Reflection\Types\Boolean;

class PlanDeatilRequest
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
		$request = $this->request;
		$rules = [
			'energy_type' => ['required', new EnergyType($request)],
			'visit_id' => 'required',
		];
		if($this->request->energy_type == 'electricitygas'){
			$rules['gas_plan_id'] = 'required';
			
		}elseif($this->request->energy_type == 'gas'){
			$rules['gas_plan_id'] = 'required';
		}elseif($this->request->energy_type == 'electricity'){
			$rules['electricity_plan_id'] = 'required';
		}
		return $rules;
	}

	public function messages()
	{
		$message = array();
		$message = [
			'visit_id.required' => "Please enter valid Visit id",
			'gas_plan_id.required' => "Please enter valid gas plan id ",
			'electricity_plan_id.required' => "Please enter valid electricity plan id",
		];
		return $message;
	}
}
