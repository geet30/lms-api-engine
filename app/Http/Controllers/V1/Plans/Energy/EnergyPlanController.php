<?php

namespace App\Http\Controllers\V1\Plans\Energy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Energy\{PlanListRequest, SaveAuthTokenRequest};
use App\Traits\LambdaTokenEx\LambdaTokenEx;
use App\Http\Requests\Energy\PlanDeatilRequest;
use App\Repositories\Energy\FilterOptions;
use App\Models\{Lead, PlanEnergy};

class EnergyPlanController extends Controller
{
    use LambdaTokenEx;
    protected $lead;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->lead = new Lead();
    }

    public function  PostPlanListing(Request $request)
    {
        $data = [];
        $sortedPlans['combined_plans_data'] = [];
        $reqObj = new PlanListRequest($request);
        $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
        $status = true;
        $statusCode = HTTP_STATUS_OK;
        $message = HTTP_SUCCESS;
       

        if ($validator->fails()) {
            $status = false;
            $statusCode = HTTP_STATUS_VALIDATION_ERROR;
            $message = HTTP_ERROR;
            $data = $validator->errors();

            return response()->json([
                'status' => $status,
                'message' => $message,
                'response' => $data,
            ], $statusCode);
        }  else {
            $CompletePostCode = explode(',', $request['post_code']);
            $allProviders = PlanEnergy::availableProviders($request);
            $moveInDays = getMoveinDays($request);
            if ($request['energy_type'] == 'electricitygas') {

                $elecPlans = self::getElectrictyPlans($request, $allProviders, $CompletePostCode, $moveInDays);
               
                $gasPlans =   self::getGasPlans($request, $allProviders, $CompletePostCode, $moveInDays);
                
                $allPlans['elec'] = $elecPlans['plans'];
                $allPlans['elec_providers'] = $elecPlans['providers'];
                $allPlans['gas'] = $gasPlans['plans'];
                $allPlans['gas_providers'] = $gasPlans['providers'];
              
            } elseif ($request['energy_type'] == 'electricity') {
                $elecPlans = self::getElectrictyPlans($request, $allProviders, $CompletePostCode, $moveInDays);
                $allPlans['elec'] = $elecPlans['plans'];
                $allPlans['elec_providers'] = $elecPlans['providers'];
                $allPlans['gas_providers'] = [];
            } elseif ($request['energy_type'] == 'gas') {
                $gasPlans = self::getGasPlans($request, $allProviders, $CompletePostCode, $moveInDays);
                
                $allPlans['gas'] = $gasPlans['plans'];
                $allPlans['gas_providers'] = $gasPlans['providers'];
                $allPlans['elec_providers'] = [];
            }
           
            if (isset($allPlans['elec']))
                $allSortPlans['electricity'] =  PlanEnergy::sortPlans($allPlans['elec'], $request['energy_type']);
            else
                $allSortPlans['electricity'] = [];

            if (isset($allPlans['gas']))
                $allSortPlans['gas'] =  PlanEnergy::sortPlans($allPlans['gas'], $request['energy_type']);
            else
                $allSortPlans['gas'] = [];

            if ($request['energy_type'] == 'electricity' ||$request['energy_type'] == 'electricitygas') {
                $sortedPlans['elec_ids'] = array_column($allSortPlans['electricity'], 'tariff_type', 'id');
            }
           
            $allSortPlans=  FilterOptions::applyFilters($allSortPlans,$request['filter_selection']);
            $providerData = PlanEnergy::getProvidersData($allProviders, $allPlans['elec_providers'], $allPlans['gas_providers'], $request['energy_type']);
            if ($request['energy_type'] == 'electricitygas') {
                $combine = PlanEnergy::comboPlans($allSortPlans);
               
                $sortedPlans['combined_plans'] = $combine['combinePlan_id'];
                $sortedPlans['combined_plans_data'] = $combine['combinePlan'];
                $sortedPlans['combo_plans'] = $combine['comboPlan_id'] + $combine['combinePlan_id'];
               
               // $filters = FilterOptions::getPlanFilterOptions($sortedPlans, "electricitygas"); 
                
            }else{

             //   $filters = FilterOptions::getPlanFilterOptions($sortedPlans, $request['energy_type']);
            }
            if ((isset($allSortPlans['electricity']) && $allSortPlans['electricity'] != null)) {
                $dualKeys = array_keys(array_column($allSortPlans['electricity'], 'dual_only', 'id'), "0"); //dual_only =0
                foreach ($allSortPlans['electricity'] as $key => $elecPlan) {
                    //check dual only plans
                    if (!in_array($elecPlan['id'], $dualKeys)) {
                        unset($allPlans['electricity'][$key]);
                    }
                    $sortedPlans["electricity"][]['id'] = $elecPlan["id"];
                    $sortedPlans["plans"]["electricity"][$elecPlan["id"]] = $elecPlan;
                }
            }

            if ((isset($allSortPlans['gas']) && $allSortPlans['gas'] != null)) {
                $dualGasKeys = array_keys(array_column($allSortPlans['gas'], 'dual_only', 'id'), "0"); //dual_only =0
                foreach ($allPlans['gas'] as $key => $gasPlan) {
                    //check dual only plans
                    if (!in_array($gasPlan['id'], $dualGasKeys)) {
                        unset($allPlans['gas'][$key]);
                    }
                    $sortedPlans["gas"][]['id'] = $gasPlan["id"];
                    $sortedPlans["plans"]["gas"][$gasPlan["id"]] = $gasPlan;
                }
            }
            $planForFiter['combined_plans'] = $sortedPlans["combined_plans_data"];
            $planForFiter['electricity'] = isset($sortedPlans["plans"]["electricity"])?$sortedPlans["plans"]["electricity"]:[] ;
            $planForFiter['gas'] = isset($sortedPlans["plans"]["gas"])?$sortedPlans["plans"]["gas"]:[];
            $filters = FilterOptions::getPlanFilterOptions($planForFiter, "electricitygas");
           
            $sortedPlans["All_plans"] = isset($sortedPlans["plans"]) ? $sortedPlans["plans"] : '';
            $sortedPlans['providers'] = $providerData;
            $sortedPlans['filters'] = $filters;
            $sortedPlans['applied_filter'] = $request['filter_selection'];

            unset($sortedPlans["plans"]);
            unset($sortedPlans["combined_plans_data"]);
            $data[] = $sortedPlans;

            if ((isset($sortedPlans['electricity']) && !empty($sortedPlans['electricity'])) || (!empty($sortedPlans['gas']) && isset($sortedPlans['gas'])) || (isset($response_data['combined_plans']) && !empty($response_data['combined_plans'])) || (isset($response_data['combo_plans']) && !empty($response_data['combo_plans']))) {

                $response = ['status' => 1, 'message' => 'Plan(s) found successfully.', 'data' => $sortedPlans];
                $status = 200;
            } else {
                $response = ['status' => 1, 'message' => 'No found any plan for given session id.'];
                $status = 200;
            }

            return $response;
        }
    }
    function getElectrictyPlans($request, $allProviders, $CompletePostCode, $moveInDays)
    {
        $meterType = setMeterType($request);
       
        $allProvidersData = $allProviders;
        $calculatedElecPlan = [];

        $allProviders = $allProviders->pluck('relational_user_id');
       
        $distributorArr = PlanEnergy::availableDistributor($request['elec_distributor_id']);

        if ($request['moving_house'] == 1) {
            $allProviders = PlanEnergy::getMoveInProviders($distributorArr, $request, $moveInDays, $allProviders, 1);
        }
        //$allProviders = PlanEnergy::checkAssginedPostcode($allProviders, $request,$CompletePostCode);
        $gasOnly = false;
        $currentProvider = $request['electricity_provider'];
        if($request['life_support'] == 1){
            if ($request['life_support_energy_type'] == 3 || $request['life_support_energy_type'] == 1) {
                $lifeSuport = 1;
                $lifeSuportEnergy = 1;
            } else {
                $lifeSuport = 0;
                $lifeSuportEnergy = 0;
            }
        }else{
            $lifeSuport = 0;
            $lifeSuportEnergy = 0;

        }
        
        $allProviders = PlanEnergy::checkproviderPermissions($allProviders, $lifeSuport, $lifeSuportEnergy, $currentProvider, $request, $gasOnly);
        $providerSettingData = $allProviders;
        $allProviders = $allProviders->pluck('user_id');
       
        $planRateIds = PlanEnergy::getElecPlanRateIds($request, $meterType, $allProviders, $distributorArr);
        
        $elecPlans = PlanEnergy::getElecPlans($request, $meterType, $allProviders, $planRateIds, $distributorArr, $providerSettingData);

        if ($request['electricity_bill'] == 1) {
            $calculatedElecPlan['plans'] = PlanEnergy::getElectricityPlansWithBill($elecPlans['plans'], $CompletePostCode[0], $request, $meterType);
        } else {
            $calculatedElecPlan['plans'] = PlanEnergy::getElectricityPlansWithoutBill($elecPlans['plans'], $CompletePostCode[0], $request, $meterType);
        }
       
        $calculatedElecPlan['providers'] = $elecPlans['providers'];

        return $calculatedElecPlan;
    }
    function getGasPlans($request, $allProviders, $CompletePostCode, $moveInDays)
    {
        $gasMeterType = 'gas_peak_offpeak';
        $allProvidersData = $allProviders;
        $calculatedGasPlan = [];
        $allProviders = $allProviders->pluck('relational_user_id');

        $distributorArr = PlanEnergy::availableDistributor($request['gas_distributor_id']);
        if ($request['energy_type'] == 'gas') {
            $gasOnly = true;
        } else {
            $gasOnly = false;
        }
        $currentProvider = $request['gas_provider'];
        if($request['life_support'] == 1){
            if ($request['life_support_energy_type'] == 3 || $request['life_support_energy_type'] == 1) {
                $lifeSuport = 1;
                $lifeSuportEnergy = 1;
            } else {
                $lifeSuport = 0;
                $lifeSuportEnergy = 0;
            }
        }else{
            $lifeSuport = 0;
            $lifeSuportEnergy = 0;

        }
       
        $allProviders = PlanEnergy::checkproviderPermissions($allProviders, $lifeSuport, $lifeSuportEnergy, $currentProvider, $request, $gasOnly);

        $providerSettingData = $allProviders;
        $allProviders = $allProviders->pluck('user_id');
        
        $gasPlanRateIds = PlanEnergy::getGasPlanRateIds($request, $gasMeterType, $allProviders);
       
        $gasPlans = PlanEnergy::getGasPlans($request, $gasMeterType, $allProviders, $gasPlanRateIds, $distributorArr, $providerSettingData);
       
        if ($request['gas_bill'] == 1) {
            $calculatedGasPlan['plans'] = PlanEnergy::getGasPlansWithBill($gasPlans['plans'], $request, $CompletePostCode[0], $gasMeterType);
        } else {
            $calculatedGasPlan['plans'] = PlanEnergy::getGasPlansWithoutBill($gasPlans['plans'], $CompletePostCode[0], $request, $gasMeterType);
        }
       
        $calculatedGasPlan['providers'] =  $gasPlans['providers'];
        $calculatedGasPlan['plans'] =  $calculatedGasPlan['plans'];
        

        return $calculatedGasPlan;
    }



    /**
     * Author:Harsimran(28-March-2022)
     * get street code
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getStreetCodes(Request $request)
    {
        try {
            $response = "";
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:harvinder singh(8-april-2022)
     * get street code
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

    public function PlanDeatils(Request $request)
    {
        $data = [];
        $reqObj = new PlanDeatilRequest($request);
        $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
        $status = true;
        $statusCode = HTTP_STATUS_OK;
        $message = HTTP_SUCCESS;
        $planDeatil['gas'] = '';
        $planDeatil['electricity'] = '';
        if ($validator->fails()) {
            $status = false;
            $statusCode = HTTP_STATUS_VALIDATION_ERROR;
            $message = HTTP_ERROR;
            $data = $validator->errors();

            return response()->json([
                'status' => $status,
                'message' => $message,
                'response' => $data,
            ], $statusCode);
        } else {
            if($request->energy_type == 'electricitygas'){
                $planDeatil['electricity'] = PlanEnergy::getElecPlanDeatils($request);
                $planDeatil['gas'] = PlanEnergy::getGasPlanDeatils($request);
            }elseif($request->energy_type == 'electricity'){
                $planDeatil['electricity'] = PlanEnergy::getElecPlanDeatils($request);
            }else{
                $planDeatil['gas'] = PlanEnergy::getGasPlanDeatils($request);
            }
            
            if ($planDeatil['electricity'] != '' || $planDeatil['gas'] != '') {
                $response = ['status' => 1, 'message' => 'Success', 'data' => $planDeatil];
                $status = 200;
            } else {
                $response = ['status' => 0, 'message' => 'Plan details is not found'];
                $status = 400;
            }

            return response()->json($response, $status);
        }
    }
}
