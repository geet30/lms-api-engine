<?php

namespace App\Traits\Lead;

use Illuminate\Support\Facades\DB;

use App\Models\{EnergyConnectionDetail};

/**
 * Lead Methods model.
 * Author: Sandeep Bangarh
 */

trait QueryMethods
{
	public static $start, $end, $service;
	/**
	 * Get single sale record with Raw Query
	 */
	static function getFirstLead($conditions, $columns = '*', $withVisitor = null, $withProduct = null, $withJourney = null, $withConnection = null, $withBillDetails = null, $withAddress = null, $withMarketing = null, $withProvider = null, $withAffiliate = null, $withInfo = null, $withPlan = null)
	{
		$query = DB::table('leads');
		$query = static::withJoins($query, $withVisitor, $withProduct, $withJourney, $withConnection, $withBillDetails, $withAddress, $withMarketing, $withProvider, $withAffiliate, $withInfo, $withPlan);

		return $query->where($conditions)->select($columns)->first();
	}

	static function getData($conditions, $columns = '*', $withVisitor = null, $withProduct = null, $withJourney = null, $withConnection = null, $withBillDetails = null, $withAddress = null, $withMarketing = null, $withProvider = null, $withAffiliate = null, $withInfo = null, $withPlan = null)
	{
		$query = DB::table('leads');
		$query = static::withJoins($query, $withVisitor, $withProduct, $withJourney, $withConnection, $withBillDetails, $withAddress, $withMarketing, $withProvider, $withAffiliate, $withInfo, $withPlan);
		if (self::$start || self::$end) {
			return $query->whereIn('leads.status', [1,2])->where($conditions)->skip(self::$start)->take(self::$end)->select($columns)->orderBy('leads.lead_id', 'desc')->get()->unique('product_id');
		}
		return $query->where($conditions)->select($columns)->orderBy('leads.lead_id', 'desc')->get();
	}

	static function isServiceInRequest() {
		$serviceId = request()->service_id??null;
		if (!$serviceId) {
			$serviceId = request()->header('ServiceId')??null;
		}
		return $serviceId;
	}

	static function getService($int=false)
	{
		$serviceId = request()->header('ServiceId')??1;
		if (request()->has('service_id')) $serviceId = request()->service_id;

		if (self::$service) {
			if ($int) return self::serviceStringToInt();
			return self::$service;
		}

		if ($int) return $serviceId;
		$service = 'energy';
		switch ($serviceId) {
			case 1:
				$service = 'energy';
				break;
			case 2:
				$service = 'mobile';
				break;
			case 3:
				$service = 'broadband';
				break;

			default:
				# code...
				break;
		}
		return $service;
	}

	static function serviceStringToInt () {
		$service = null;
		switch (self::$service) {
			case 'energy':
				$service = 1;
				break;
			case 'mobile':
				$service = 2;
				break;
			case 'broadband':
				$service = 3;
				break;
			default:
				# code...
				break;
		}
		return $service;
	}

	static function withJoins($query, $withVisitor, $withProduct, $withJourney, $withConnection, $withBillDetails, $withAddress, $withMarketing, $withProvider, $withAffiliate, $withInfo, $withPlan)
	{
		$service = self::getService();

		if ($withVisitor) {
			$query = $query->join('visitors', 'leads.visitor_id', '=', 'visitors.id');
		}

		if ($withProduct) {
			$query = $query->leftjoin('sale_products_' . $service, 'leads.lead_id', '=', 'sale_products_' . $service . '.lead_id');
			if ($withProvider) {
				$query = $query->leftjoin('providers', 'sale_products_' . $service.'.provider_id', '=', 'providers.user_id');
			}
			if ($withPlan) {
				$table = 'plans_'.$service;
				if ($service == 'broadband') $table = 'plans_broadbands';
				$query = $query->leftjoin($table, 'sale_products_' . $service.'.plan_id', '=', $table.'.id');
				if (in_array($service, ['broadband','mobile'])) {
					$serviceId = self::getService(true);
					$query = $query->leftjoin('connection_types', $table.'.connection_type', '=', 'connection_types.local_id')->where('connection_types.service_id', $serviceId)->where('connection_types.status', 1)->where('connection_type_id', 1);
				}
				
			}
		}

		if ($withJourney) {
			$query = $query->leftjoin('lead_journey_data_' . $service, 'leads.lead_id', '=', 'lead_journey_data_' . $service . '.lead_id');
		}
		if ($withBillDetails) {
			$query = $query->leftjoin($service . '_bill_details', 'leads.lead_id', '=', $service . '_bill_details.lead_id');
		}

		if ($withConnection && in_array($service, ['energy','mobile'])) {
			$query = $query->leftjoin('sale_product_' . $service . '_connection_details', 'leads.connection_address_id', '=', 'sale_product_' . $service . '_connection_details.id');
		}

		if ($withAddress) {
			$query = $query->leftjoin('visitor_addresses', 'leads.connection_address_id', '=', 'visitor_addresses.id');
		}

		if ($withMarketing) {
			$query = $query->leftjoin('marketing', 'leads.lead_id', '=', 'marketing.lead_id');
		}

		if ($withAffiliate) {
			$query = $query->leftjoin('affiliates', 'leads.affiliate_id', '=', 'affiliates.user_id');
			$query = $query->leftjoin('affiliates as subaff', 'leads.sub_affiliate_id', '=', 'subaff.user_id');
		}

		if ($withInfo) {
			if ($service == 'energy') {
				$query = $query->leftjoin('visitor_informations_energy', 'leads.visitor_info_energy_id', '=', 'visitor_informations_energy.id');
			}
			if ($service != 'energy') {
				$query = $query->leftjoin('visitor_identifications', 'leads.visitor_primary_identifications_id', '=', 'visitor_identifications.id');
				$query = $query->leftjoin('visitor_identifications as secondary_identifications', 'leads.visitor_secondary_identifications_id', '=', 'secondary_identifications.id');
			}
			
		}

		return $query;
	}

	static function updateData($conditions, $data)
	{
		return self::where($conditions)->update($data);
	}

	static function checkDuplicateSaleForEnergy($leadId, $visitor)
	{
		$service = self::getService();
		$query = DB::table('leads')
			->join('sale_products_' . $service, 'leads.lead_id', '=', 'sale_products_' . $service . '.lead_id')
			->join('visitors', 'leads.visitor_id', '=', 'visitors.id')
			->join('lead_journey_data_'. $service, 'leads.lead_id', '=', 'lead_journey_data_' . $service . '.lead_id')
			->join('sale_product_' . $service . '_connection_details', 'leads.connection_address_id', '=', 'sale_product_' . $service . '_connection_details.id')
			->where('visitors.dob', date('Y-m-d', strtotime($visitor->dob)))
			->where('visitors.email', $visitor->email)
			->where('leads.status', 1)
			->where('leads.lead_id', '!=', $leadId);
		if ($service == 'energy') {
			$query = $query->where('connection_street_number', $visitor->connection_street_number)
				->where('connection_street_name', $visitor->connection_street_name)
				->where('connection_suburb', $visitor->connection_suburb)
				->where('connection_post_code', $visitor->connection_post_code)
				->where('connection_state', $visitor->connection_state);
		}

		return $query->where('sale_products_' . $service . '.product_type', $visitor->product_type)->exists();
	}

	static function setJaurneryResponse($journeyDatas,$postCode)
	{
		//$journeyData = [];
		//$journeyData = $journeyData->groupBy('enegy_type');
		$responseData = [];
		foreach($journeyDatas as $journeyData){

			
		$responseData['post_code'] = isset($postCode['CompletePostCode'])?$postCode['CompletePostCode']:'';
		
		$responseData['property_type'] = $journeyData->property_type;
		
		
		$responseData['life_support'] = $journeyData->life_support;
		$responseData['life_support_energy_type'] = $journeyData->life_support_energy_type;
		$responseData['life_support_value'] = $journeyData->life_support_value;
		$responseData['moving_house'] = $journeyData->moving_house;
		$movingDate = date("d/m/Y", strtotime($journeyData->moving_date));
		$responseData['moving_date'] = $movingDate;
		$responseData['prefered_move_in_time'] = $journeyData->prefered_move_in_time;
		$responseData['credit_score'] = $journeyData->credit_score;

		if ($journeyData->energy_type == 1) {
			$responseData['solar_panel'] = $journeyData->solar_panel;
			$responseData['solar_options'] = $journeyData->solar_options;
			$responseData['solar_usage'] = $journeyData->solar_usage;	
			$elec = true;
			$responseData['energy_type'] = 'electricity';
			$responseData['elec_distributor_id'] = $journeyData->distributor_id;
		//	$responseData['gas_distributor_id'] = $journeyData[1]->distributor_id;
			$responseData['electricity_provider'] = $journeyData->previous_provider_id;
		//	$responseData['gas_provider'] = $journeyData[1]->previous_provider_id;
		} elseif ($journeyData->energy_type == 2) {
			if(isset($elec)){
				$responseData['energy_type'] = 'electricitygas';
			}else{
				$responseData['energy_type'] = 'gas';
				
			}
			
			$responseData['gas_distributor_id'] = $journeyData->distributor_id;
			
		} else {
			$responseData['energy_type'] = 'electricity';
			$responseData['elec_distributor_id'] = $journeyData->distributor_id;
			$responseData['electricity_provider'] = $journeyData->previous_provider_id;
		}
		if ($journeyData->energy_type == 1) {

			$responseData['elec_concession_rebate_amount'] = isset($journeyData->elec_concession_rebate_amount)?$journeyData->elec_concession_rebate_amount:0;
			$responseData['elec_concession_rebate_ans'] =  isset($journeyData->elec_concession_rebate_ans)?$journeyData->elec_concession_rebate_ans:0; 

			if($journeyData->bill_available != null){
				$responseData['electricity_bill'] = $journeyData->bill_available;
				$responseData['electricity_usage'] = $journeyData->usage_level;
				$billStart =  date("d/m/Y", strtotime($journeyData->bill_start_date));
				$billEnd =  date("d/m/Y", strtotime($journeyData->bill_end_date));
				$responseData['electricity_bill_startdate'] = $billStart;
				$responseData['electricity_bill_enddate'] = $billEnd;
				$responseData['electricity_bill_amount'] = $journeyData->bill_amount;
				if($journeyData->meter_type != null){
					$responseData['meter_type'] = $journeyData->meter_type;
					$responseData['tariff_type'] = $journeyData->tariff_type;
				}
			
				$responseData['electricity_peak_usage'] = $journeyData->peak_usage;
				$responseData['electricity_off_peak_usage'] = $journeyData->off_peak_usage;
				$responseData['shoulder_usage'] = $journeyData->shoulder_usage;
				$responseData['shoulder_usage'] = $journeyData->shoulder_usage;

				$responseData['control_load_one_usage'] = $journeyData->control_load_one_usage;
				$responseData['control_load_two_usage'] = $journeyData->control_load_two_usage;
				$responseData['control_load_one_off_peak'] = $journeyData->control_load_one_off_peak;
				$responseData['control_load_one_shoulder'] = $journeyData->control_load_one_shoulder;
				$responseData['control_load_two_off_peak'] = $journeyData->control_load_two_off_peak;
				$responseData['control_load_two_shoulder'] = $journeyData->control_load_two_shoulder;
				$responseData['control_load_timeofuse'] = $journeyData->control_load_timeofuse;
			}else{
				$responseData['electricity_bill'] = 0;
				$responseData['meter_type'] = 'peak';
			}
			
			if($journeyData->demand_tariff == 1){
				$responseData['demand'] = $journeyData->demand_tariff;
				$responseData['demand_rate_last_step'] = $journeyData->demand_rate_last_step;
				$responseData['master_demand_tarriff'] = $journeyData->demand_tariff_code;
				$responseData['demand_data']['demand_tariff_code'] = $journeyData->demand_tariff_code;
				$responseData['demand_data']['demand_meter_type'] = $journeyData->demand_meter_type;
				$responseData['demand_data']['demand_usage_type'] = $journeyData->demand_usage_type;
				$responseData['demand_data']['demand_rate1_peak_usage'] = $journeyData->demand_rate1_peak_usage;
				$responseData['demand_data']['demand_rate1_off_peak_usage'] = $journeyData->demand_rate1_off_peak_usage;
				$responseData['demand_data']['demand_rate1_shoulder_usage'] = $journeyData->demand_rate1_shoulder_usage;
				$responseData['demand_data']['demand_rate1_days'] = $journeyData->demand_rate1_days;
				$responseData['demand_data']['demand_rate2_peak_usage'] = $journeyData->demand_rate2_peak_usage;
				$responseData['demand_data']['demand_rate2_off_peak_usage'] = $journeyData->demand_rate2_off_peak_usage;
				$responseData['demand_data']['demand_rate2_shoulder_usage'] = $journeyData->demand_rate2_shoulder_usage;
				$responseData['demand_data']['demand_rate2_days'] = $journeyData->demand_rate2_days;
				$responseData['demand_data']['demand_rate3_peak_usage'] = $journeyData->demand_rate3_peak_usage;
				$responseData['demand_data']['demand_rate3_off_peak_usage'] = $journeyData->demand_rate3_off_peak_usage;
				$responseData['demand_data']['demand_rate3_shoulder_usage'] = $journeyData->demand_rate3_shoulder_usage;
				$responseData['demand_data']['demand_rate3_days'] = $journeyData->demand_rate3_days;
				$responseData['demand_data']['demand_rate4_peak_usage'] = $journeyData->demand_rate4_peak_usage;
				$responseData['demand_data']['demand_rate4_off_peak_usage'] = $journeyData->demand_rate4_off_peak_usage;
				$responseData['demand_data']['demand_rate4_shoulder_usage'] = $journeyData->demand_rate4_shoulder_usage;
				$responseData['demand_data']['demand_rate4_days'] = $journeyData->demand_rate4_days;
				$responseData['demand_data']['demand_rate4_days'] = $journeyData->demand_rate4_days;
			}
			

		}
		if (  $journeyData->energy_type == 2) {
				$responseData['gas_concession_rebate_ans'] = $journeyData->gas_concession_rebate_ans;
				$responseData['gas_concession_rebate_amount'] = $journeyData->gas_concession_rebate_amount;
			if($journeyData->bill_available != null){
				$responseData['gas_bill'] = $journeyData->bill_available;
				$responseData['gas_usage_level'] = $journeyData->usage_level;
				$responseData['gas_usage_level'] = $journeyData->usage_level;
				$billStart =  date("d/m/Y", strtotime($journeyData->bill_start_date));
				$billEnd =  date("d/m/Y", strtotime($journeyData->bill_end_date));
				$responseData['gas_bill_startdate'] = $billStart;
				$responseData['gas_bill_enddate'] = $billEnd;
				$responseData['gas_bill_amount'] = $journeyData->bill_amount;
				$responseData['gas_peak_usage'] = $journeyData->peak_usage;
				$responseData['gas_off_peak_usage'] = $journeyData->off_peak_usage;
				$responseData['gas_provider'] = $journeyData->previous_provider_id;

			
			}else{
				$responseData['gas_bill'] = 0;
			}

			
		}

		if(!empty($journeyData->filters)){
			$responseData['filter']= true;
			$responseData['filter_selection']= json_decode($journeyData->filters);
			
		}else{
			$responseData['filter']= false;
			$responseData['filter_selection']= $journeyData->filters;
		}
		}
		
		return $responseData;
	}

	static function isSaleCreated ($visitId) {
		return self::where('lead_id', $visitId)->where('status', 2)->exists();
	}	
	static function getJourneyBroadbandData($leadId){
		return  DB::table('leads')
		->select('lead_journey_data_broadband.connection_type','lead_journey_data_broadband.technology_type','lead_journey_data_broadband.movein_type','visitor_addresses.address','sale_products_broadband.provider_id','sale_products_broadband.plan_id','sale_products_broadband.id as sale_product_id')
		->join('lead_journey_data_broadband','leads.lead_id','lead_journey_data_broadband.lead_id')
		->join('visitor_addresses','visitor_addresses.id','leads.connection_address_id')
		->leftJoin('sale_products_broadband','sale_products_broadband.lead_id','leads.lead_id')->where('leads.lead_id',$leadId)->first();
	}
}
