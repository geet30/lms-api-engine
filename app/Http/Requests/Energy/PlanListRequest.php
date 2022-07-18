<?php

namespace App\Http\Requests\Energy;

use App\Rules\Plan\Energy\EnergyType;
use App\Rules\Plan\Energy\PropertyType;
use App\Rules\Plan\Energy\ValidateMoveinDate;
use App\Rules\Plan\Energy\ValidateElecDistributor;
use App\Rules\Plan\Energy\ValidateGasDistributor;
use App\Rules\Plan\Energy\ValidatePostCode;
use phpDocumentor\Reflection\Types\Boolean;

class PlanListRequest
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

		$service=  $this->request->header('serviceId');
		
		$rules = array();
		$request = $this->request;

		if($service == 1){
			$rules = [
				'post_code' => ['required', new ValidatePostCode($request)],
				'energy_type' => ['required', new EnergyType($request)],
				'property_type' => 'required|in:1, 2',
				'moving_house' => 'required|boolean',
				'life_support' => 'required|boolean',
				'life_support_value' => 'required_if:life_support,==,1|string',
				'life_support_energy_type' => 'required_if:life_support,==,1|in:1,2,3',
				'credit_score' => 'numeric',
			];
	
			if ($request->has('energy_type') && $request->get('energy_type') == 'electricity') {
				$result = $this->electricity($request);
			} else if ($request->has('energy_type') && $request->get('energy_type') == 'gas') {
				$result = $this->gas($request);
			} else if ($request->has('energy_type') && $request->get('energy_type') == 'electricitygas') {
	
				$result = $this->electricitygas($request);
			}
			if (!empty($result)) {
				$rules = array_merge($rules, $result['rules']);
			}
		}elseif($service == 2){
			$rules = [
					'plan_type' => 'required|in:1,2',
					'connection_type'=>'required|in:1,2',
					];
		

		}elseif($service == 3){
			$rules['visit_id']         ='required';
			$rules['connection_type']  ='required';
			$rules['technology_name']  = 'required';
			$rules['movin_type']       = 'required';
			if($this->request->has('movin_type') && $this->request->movin_type == 'yes'){
                $rules['movin_date'] = 'required';
			}			
			if($this->request->has('is_agent')){
				$rules['current_provider'] = 'required';
				$rules['no_of_user']       = 'required';
				$rules['use_of_internet']  = 'required';
				$rules['streaming_type']   = 'required';
				$rules['spend_crr_bill']   = 'required';
			}
		}
		return $rules;
	}

	public function messages()
	{
		$message = array();
		$message = [
			'plan_type.required'=>'Plan type is required',  
			'property_type.required' => "Please enter valid property type",
			'property_type.in' => "Please enter either 1 or 2",
			'solar_panel.required' => "Please enter solar panel",
			'life_support.required' => "Please enter life support",
			'life_support_value.required' => "Please enter life support value",
			'life_support_energy_type.required' => "Please enter life support",
			'life_support.required' => "Please enter life support",
			'moving_house.required' => "Please enter moving house",
			'life_support_energy_type.required_if' => "Please enter life support energyt type",

		];
		return $message;
	}

	private function electricity($request = null)
	{
		try {
			$rules = [];
			$electricity_bill_array = [];
			$rules['solar_panel'] = 'required|boolean';
			$rules['elec_distributor_id'] = ['required', new ValidateElecDistributor($request)];
			if ($request->has('moving_house') && $request->get('moving_house') == 1) {
				$rules['moving_date'] = ['bail', 'required', 'date_format:d/m/Y', 'after:today', new ValidateMoveinDate($request)];
			} else if ($request->has('moving_house') && $request->get('moving_house') == 0) {
				if ($request->has('electricity_bill')) {
					$rules['electricity_bill'] = 'required|boolean';
				}
				if ($request->has('electricity_bill') && $request->get('electricity_bill') == 1) {
					$electricity_bill_array = $this->electricity_bill($request);
					$rules = array_merge($rules, $electricity_bill_array['rules']);
				}
			}
			return ['rules' => $rules];
		} catch (\Exception $e) {
			$response = ['status' => false, 'message' => 'Something went wrong, Please try agaon later'];
			$status = 400;
			return response()->json($response, $status);
		}
	}

	private function gas($request = null)
	{
		try {
			$rules = [];
			$rules['gas_distributor_id'] = ['required', new ValidateGasDistributor($request)];

			if ($request->has('moving_house') && $request->get('moving_house') == 1) {
				$rules['moving_date'] = ['bail', 'required', 'date_format:d/m/Y', 'after:today', new ValidateMoveinDate($request)];
			} else if ($request->has('moving_house') && $request->get('moving_house') == 0) {
				if ($request->has('gas_bill')) {
					$rules['gas_bill'] = 'required|boolean';
				}
				if ($request->has('gas_bill') && $request->get('gas_bill') == 1) {

					$rules['gas_provider'] = 'required';
					$rules['gas_bill_startdate'] = 'required|date_format:d/m/Y|before:tomorrow';
					$rules['gas_bill_enddate'] = 'required|date_format:d/m/Y|after_or_equal:' . $request->get('gas_bill_startdate') . '|before:tomorrow';
					$rules['gas_peak_usage'] = 'required|numeric';
					$rules['gas_off_peak_usage'] = 'numeric';
				}
			} else {
			}

			return ['rules' => $rules];
		} catch (\Exception $e) {
			$response = ['status' => false, 'message' => 'Something went wrong, Please try agaon later'];
			$status = 400;
			return response()->json($response, $status);
		}
	}

	private function electricitygas($request = null)
	{
		try {

			$rules = [];
			$rules['elec_distributor_id'] = ['required', new ValidateElecDistributor($request)];
			$rules['gas_distributor_id'] = ['required', new ValidateGasDistributor($request)];

			$rules['solar_panel'] = 'required|boolean';
			if ($request->has('moving_house') && $request->get('moving_house') == 1) {
				$rules['moving_date'] = ['bail', 'required', 'date_format:d/m/Y', 'after:today', new ValidateMoveinDate($request)];
				$rules['electricity_bill'] = 'required|boolean';
				$rules['gas_bill'] = 'required|boolean';
			} else if ($request->has('moving_house') && $request->get('moving_house') == 0) {
				if (($request->has('electricity_bill') && $request->get('electricity_bill') == 1)) {
					$electricity_bill_array = $this->electricity_bill($request);
					$rules = array_merge($rules, $electricity_bill_array['rules']);
				}
				$rules['gas_bill'] = 'required|boolean';

				if (($request->has('gas_bill') && $request->get('gas_bill') == 1)) {

					if ($request->has('gas_bill_amount')) {
						$rules['gas_bill_amount'] = 'numeric';
					}
					$rules['gas_provider'] = 'required';
					$rules['gas_bill_startdate'] = 'required|date_format:d/m/Y|before:tomorrow';
					$rules['gas_bill_enddate'] = 'required|date_format:d/m/Y|after_or_equal:' . $request->get('gas_bill_startdate') . '|before:tomorrow';

					$rules['gas_peak_usage'] = 'required|numeric';
					$rules['gas_off_peak_usage'] = 'numeric';
				}
			}
			return ['rules' => $rules];
		} catch (\Exception $e) {
			$response = ['status' => false, 'message' => 'Something went wrong, Please try agaon later'];
			$status = 400;
			return response()->json($response, $status);
		}
	}
	public function demandRules($request, $rules)
	{
		if ($request->has('demand') && $request->get('demand')) {
			$rules['demand_rate_last_step'] = 'required|numeric|min:1|max:4';
			if ($request->demand_rate_last_step == "") {
				return $rules;
			}
			$demadnData = $request->get('demand_data');
			$rates = range(1, $request->get('demand_rate_last_step'));
			//$propertyType = $request->get('property_type') == 'residential' ? 1 : 2;
			//$distributor_id = $request->elec_distributor_id;
			//$rules['demand_data.demand_tariff_code'] = 'required|exists:master_tariffs,id,distributor_id,' . $distributor_id . ',property_type,' . $propertyType . ',status,1';
			$rules['demand_data.demand_tariff_code'] = 'required';
			$rules['demand_data.demand_usage_type'] = 'required|integer|in:1,2';
			$rules['demand_data.demand_meter_type'] = "required_if:demand_data.demand_meter_type,!=,''|in:1,2";

			$daysValidate = false;
			$daysValidateArr = [];

			foreach ($rates as $rate) {
				$rules['demand_data.demand_rate' . $rate . '_peak_usage'] = 'required|numeric';
				$rules['demand_data.demand_rate' . $rate . '_off_peak_usage'] = 'numeric';
				$rules['demand_data.demand_rate' . $rate . '_shoulder_usage'] = 'numeric';

				if ($demadnData['demand_usage_type'] == 2 && $request->get('demand_rate_last_step') > 1) {
					$rules['demand_data.demand_rate' . $rate . '_days'] = 'required|integer|min:1';
				} elseif ($demadnData['demand_rate' . $rate . '_days'] != 0) {
					$rules['demand_data.demand_rate' . $rate . '_days'] = 'integer|min:1';
				}

				if ($demadnData['demand_usage_type'] == 1 && $demadnData['demand_rate' . $rate . '_days'] > 0) {
					$daysValidate = true;
				} elseif ($demadnData['demand_usage_type'] == 1 && $demadnData['demand_rate' . $rate . '_days'] == '') {
					$daysValidateArr['demand_data.demand_rate' . $rate . '_days'] =  'required|integer|min:1';
				}
			}

			if ($daysValidate) {
				$rules = array_merge($rules, $daysValidateArr);
			}
		}
		return $rules;
	}


	private function electricity_bill($request = null)
	{
		try {

			$rules = [];
			$rules['meter_type'] = 'required';
			$rules['electricity_provider'] = 'required';
			$rules['meter_type'] = 'required|string';
			$rules['electricity_bill_startdate'] = 'required|date_format:d/m/Y|before:tomorrow';
			$rules['electricity_bill_enddate'] = 'required|date_format:d/m/Y|after_or_equal:' . $request->get('electricity_bill_startdate') . '|before:tomorrow';
			//solar_panels is yes
			if ($request->has('solar_panel') && $request->get('solar_panel') == 1) {
				//$rules['solar_by_back'] = 'required|numeric|digits_between:1,10';
				$rules['solar_usage'] = 'required|numeric';
				if ($request->has('solor_tariff')) {
					$rules['solor_tariff'] = 'required';
				}
			}

			$rules['electricity_peak_usage'] = 'required|numeric';
			if (($request->has('meter_type') && $request->get('meter_type') == 'double')) {
				$rules['electricity_off_peak_usage'] = 'numeric';
				$controlLoadRueles = $this->controlLoadRules($request);
				$rules = array_merge($controlLoadRueles, $rules);
			} else if (($request->has('meter_type') && $request->get('meter_type') == 'timeofuse')) {
				$rules['electricity_peak_usage'] = 'required|numeric';
				$rules['electricity_off_peak_usage'] = 'required|numeric';
				$rules['shoulder_usage'] = 'numeric';
				$controlLoadRueles = $this->controlLoadRules($request);
				$rules = array_merge($controlLoadRueles, $rules);
			}
			if ($request->has('electricity_bill_amount')) {
				$rules['electricity_bill_amount'] = 'numeric';
			}
			$demandRules = $this->demandRules($request, $rules);
			$rules = array_merge($demandRules, $rules);

			return ['rules' => $rules];
		} catch (\Exception $e) {
			$response = ['status' => false, 'message' => 'Something went wrong, Please try agaon later'];
			$status = 400;
			return response()->json($response, $status);
		}
	}
	private function controlLoadRules($request = null)
	{
		$rules['control_load_one_usage'] = ['required_without:control_load_two_usage'];
		$rules['control_load_two_usage'] = ['required_without:control_load_one_usage'];
		if ($request->has('control_load_one_usage') && !empty($request->get('control_load_one_usage'))) {
			$rules['control_load_one_usage'] = 'numeric';
		}
		if ($request->has('control_load_two_usage') && !empty($request->get('control_load_two_usage'))) {
			$rules['control_load_two_usage'] = 'numeric';
		}
		if ($request->has('control_load_timeofuse') && $request->control_load_timeofuse == 1) {
			$rules['control_load_one_off_peak'] = 'numeric';
			$rules['control_load_one_shoulder'] = 'numeric';
			$rules['control_load_two_off_peak'] = 'numeric';
			$rules['control_load_two_shoulder'] = 'numeric';
		}
		return $rules;
	}
}
