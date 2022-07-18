<?php

namespace App\Traits\Broadband;

use Illuminate\Support\Facades\DB;
use App\Models\{ConnectionType, Acknowledgement, Provider, ProviderLogo, PlansBroadbandEicContent, PlansBroadband, ProviderContent, AffiliateKeys, AssignedUsers, PlansBroadbandAddon, LeadJourneyDataBroadband, PlanEnergy, PlanEicContent, CheckBoxContent, SaleProductsBroadband, Lead, SaleProductsEnergy};
use Illuminate\Support\Facades\Auth;



trait BasicCrudMethods
{
    /**
     * Author:(11-March-2022)
     * get NBN data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    static function getConnectionType($request)
    {
        try {
            $response = [];
            $data = DB::table('app_settings')->where('type', 'acknowledgement')->get();
            if ($data) {
                $response['acknowledgment_data'] = [
                    'status' =>   $data[0]->status,
                    'description' => $data[0]->content
                ];
            }

            $response['connection_type'] = ConnectionType::where([
                'status' => ConnectionType::ONE,
                'service_id' => ConnectionType::SERVICE_BROADBAND,
                'connection_type_id' => Null,
                'is_deleted' => ConnectionType::ZERO
            ])->pluck('name', 'id');
            if ($request->header('source') == '2') {
                $data = DB::table('connection_types')->where([
                    'service_id' => ConnectionType::SERVICE_BROADBAND,
                    'is_deleted' => ConnectionType::ZERO,
                    'status' => ConnectionType::ONE
                ])->whereIn('connection_type_id', [4, 5, 1])->get()->toArray();
                $response['number_of_users'] = [];
                $response['internet_for'] = [];
                $response['techno_type'] = [];
                foreach ($data as $row) {
                    if (ConnectionType::SERVICE_BROADBAND == $row->service_id && $row->connection_type_id == 4) {
                        array_push($response['number_of_users'], [
                            'id' => $row->id,
                            'local_id' => $row->local_id,
                            'name' => $row->name
                        ]);
                    }
                    if (ConnectionType::SERVICE_BROADBAND == $row->service_id && $row->connection_type_id == 5) {
                        array_push($response['internet_for'], [
                            'id' => $row->id,
                            'local_id' => $row->local_id,
                            'name' => $row->name
                        ]);
                    }
                    if (ConnectionType::SERVICE_BROADBAND == $row->service_id && $row->connection_type_id == 1) {
                        array_push($response['techno_type'], [
                            'id' => $row->id,
                            'local_id' => $row->local_id,
                            $row->name => $row->script,
                        ]);
                    }
                    if (ConnectionType::SERVICE_BROADBAND == $row->service_id && $row->connection_type_id == Null) {
                        array_push($response['connection_type'], [
                            $row->id => $row->name
                        ]);
                    }
                }
                $response['faq'] = DB::table('faq')->select('id', 'question', 'answer')->where(
                    [
                        'service_id' => 3,
                        'status' => 1,
                        'is_deleted' => 0
                    ]
                )->get()->toArray();
            }

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }

    /**
     * Author:(11-March-2022)
     * get Broadband provider  data
     * @param  \Illuminate\Http\Request  $request
     * @return array()
     */
    static function getProviderList($request)
    {
        $encrypt_api = encryptGdprData($request->header('API-KEY'));

        $cacheKey = self::getCacheKey($request, 'provider:list:');
        $cacheData = self::getDataFromCache($cacheKey);
        if ($cacheData) {
            return $cacheData;
        }

        $affiliate_id = DB::table('affiliate_keys')->where('api_key', $encrypt_api)->pluck('user_id')->first();
        /*
 source = affiliate
 relation = provider
 */
        $relation_type = 4;
        if (Auth::user()->affiliate->parent_id == Provider::ZERO) {
            $relation_type = 1;
        }


        if ($request->assigned == 1) {
            $data = Provider::select('providers.id', 'providers.service_id', 'providers.user_id', 'providers.name', 'provider_logos.url', 'provider_logos.name as provider_url')
                ->join('assigned_users', 'providers.user_id', 'assigned_users.relational_user_id')
                ->leftJoin(
                    'provider_logos',
                    function ($query) {
                        $query->on('providers.user_id', 'provider_logos.user_id')->whereNull('provider_logos.deleted_at')->where('provider_logos.category_id', ProviderLogo::HOME_PAGE)->where('provider_logos.status', ProviderLogo::STATUS_ENABLE);
                    }
                )->where(
                    [
                        'assigned_users.source_user_id' => $affiliate_id,
                        'assigned_users.service_id' => $request->header('serviceId'),
                        'providers.status' => Provider::STATUS_ENABLE,
                        'providers.is_deleted' => Provider::ZERO,
                        'assigned_users.relation_type' => $relation_type,

                    ]
                )->whereIn('providers.service_id', $request->vertical)->get();
        } else {
            $data = Provider::select('providers.id', 'providers.service_id', 'providers.user_id', 'providers.name', 'provider_logos.url', 'provider_logos.name as provider_url')
                ->leftJoin(
                    'provider_logos',
                    function ($query) {
                        $query->on('providers.user_id', 'provider_logos.user_id')->whereNull('provider_logos.deleted_at')->where('provider_logos.category_id', ProviderLogo::HOME_PAGE)->where('provider_logos.status', ProviderLogo::STATUS_ENABLE);
                    }
                )->where(
                    [
                        'providers.status' => Provider::STATUS_ENABLE,
                        'providers.is_deleted' => Provider::ZERO,

                    ]
                )->whereIn('providers.service_id', $request->vertical)->get();
        }
        $response['energy'] = [];
        $response['broadband'] = [];
        $response['mobile'] = [];
        foreach ($data as $row) {
            if (isset($row->provider_url) && !empty($row->provider_url)) {
                $providerName = $row->user_id;
                $s3fileName = str_replace("<pro-id>", $providerName, config('env.PROVIDER_LOGO'));
                $row['url'] = config('env.Public_BUCKET_ORIGIN') . config('env.DEV_FOLDER') . $s3fileName . $row->provider_url;
            } else {
                $row['url'] = '';
            }
            unset($row->provider_url);
            if ($row->service_id == 1) {
                array_push($response['energy'], $row);
            }
            if ($row->service_id == 2) {
                array_push($response['mobile'], $row);
            }
            if ($row->service_id == 3) {
                array_push($response['broadband'], $row);
            }
        }
        self::addDataIntoCache($cacheKey, $response);
        return $response;
    }
    /**
     * Author:(16-March-2022)
     * get EIC data
     * @param  \Illuminate\Http\Request  $request
     * @return array $data
     */
    static function getEicData($request)
    {
        try {
            if ($request->service_id == 1) {
                return self::getEnergyEic($request);
            } else if ($request->service_id == 2) {
                return self::getMobileEic($request);
            } else if ($request->service_id == 3) {
                return self::getBroadbandEic($request);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:(16-March-2022)
     * get EIC data of energy
     * @param  \Illuminate\Http\Request  $request
     * @return array $data
     */
    static function getEnergyEic($request)
    {
        try {
            $response = [];
            $plans = PlanEnergy::select('id', 'name', 'energy_type', 'provider_id')->with(['provider', 'planEicContents.planEicContentCheckbox'])->whereIn('id', $request->plan_id)->get();
            $company = DB::table('affiliates')->select('company_name')->where('user_id',Auth::user()->id)->first();
            $get_provider_data['affiliate_name'] = $company->company_name;
            $attributes = ['@Affiliate-Name@', '@Provider-Name@', '@Provider-Phone-Number@', '@Provider-Address@', '@Provider-Email@'];
            foreach ($plans as $plan) {
                $enrgyType = $plan->energy_type;
                $response[$enrgyType]['provider_name'] =  $plan->provider->name;
                $get_provider_data['provider_name'] = $plan->provider->name;
                $get_provider_data['phone'] = decryptGdprData($plan->provider->user->phone);
                $get_provider_data['address'] = $plan->provider->user->getUserAddress->address;
                $get_provider_data['email'] =  decryptGdprData($plan->provider->user->email);
                if (!empty($plan->planEicContents)) {
                    $response[$enrgyType]['content'] =
                        str_replace($attributes, $get_provider_data, $plan->planEicContents->content);
                    $response[$enrgyType]['checkbox'] = [];
                    foreach ($plan->planEicContents->planEicContentCheckbox as $checkbox) {
                        array_push($response[$enrgyType]['checkbox'], [
                            "checkbox_name" => "",
                            "content" => str_replace($attributes, $get_provider_data, $checkbox->content),
                            "id" => $checkbox->id,
                            "required" => $checkbox->required,
                            "validation_message" => $checkbox->validation_message
                        ]);
                    }
                } else {
                    $data = self::getProviderEic($request, $plan->provider_id, $get_provider_data, $attributes);
                    $response[$enrgyType]['content'] = isset($data['content']) ? $data['content'] : null;
                    $response[$enrgyType]['checkbox'] = isset($data['checkbox']) ? $data['checkbox'] : null;
                }
                $dmoContent = SaleProductsEnergy::where('lead_id', decryptGdprData($request->visit_id))->where('product_type', 1)->select('id', 'dmo_content')->first();
            }
            $response['dmo_content'] = isset($dmoContent->dmo_content) ? $dmoContent->dmo_content : '';
            return $response;
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:(16-March-2022)
     * get EIC data of broadband
     * @param  \Illuminate\Http\Request  $request
     * @return array $data
     */
    static function getBroadbandEic($request)
    {
        try {
            $response = [];
            $plan = PlansBroadband::select('id', 'name', 'provider_id')->with(['provider.user.getUserAddress', 'planEicContents' => function ($query) {
                $query->where('status', 1)->select('plan_id', 'content');
            }, 'planEicContentCheckbox' => function ($query) {
                $query->where('status', 1);
            }])->where('id', $request->plan_id)->first();
            $response['broadband']['provider_name'] =  $plan->provider->name;
            $company = DB::table('affiliates')->select('company_name')->where('user_id',Auth::user()->id)->first();
            $get_provider_data['affiliate_name'] = $company->company_name;
            $get_provider_data['provider_name'] = $plan->provider->name;
            $get_provider_data['phone'] = decryptGdprData($plan->provider->user->phone);
            $get_provider_data['address'] = $plan->provider->user->getUserAddress->address;
            $get_provider_data['email'] =  decryptGdprData($plan->provider->user->email);
            $attributes = ['@Affiliate-Name@', '@Provider-Name@', '@Provider-Phone-Number@', '@Provider-Address@', '@Provider-Email@'];
            if (isset($plan->planEicContents)) {
                $response['broadband']['content'] = str_replace($attributes, $get_provider_data, $plan->planEicContents->content);
                $response['broadband']['checkbox'] = [];
                foreach ($plan->planEicContentCheckbox as $checkbox) {
                    array_push($response['broadband']['checkbox'], [
                        "content" => str_replace($attributes, $get_provider_data, $checkbox->content),
                        "id" => $checkbox->id,
                        "required" => $checkbox->required,
                        "validation_message" => $checkbox->validation_message
                    ]);
                }
            } else {
                $data = self::getProviderEic($request, $plan->provider_id, $get_provider_data, $attributes);
                $response['broadband']['content'] = isset($data['content']) ? $data['content'] : null;
                $response['broadband']['checkbox'] = isset($data['checkbox']) ? $data['checkbox'] : null;
            }

            return $response;
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:(16-March-2022)
     * get EIC data of provider
     * @param  \Illuminate\Http\Request  $request
     * @return array $data
     */
    static function getProviderEic($request, $provider_id, $get_provider_data, $attributes)
    {
        $state_id = DB::table('provider_states')->where('name', $request->state)->pluck('id')->first();
        $provider_state_eic = ProviderContent::with('checkbox')->where([
            'provider_id' =>  $provider_id,
            'state_id' => $state_id,
            'provider_contents.type' => '14',
            'provider_contents.status' => 1,
        ])->first();
        $response = [];
        if ($provider_state_eic) {
            $response['content'] = str_replace($attributes, $get_provider_data, $provider_state_eic->description);
            $response['checkbox'] = [];
            foreach ($provider_state_eic->checkbox as $checkbox) {
                array_push($response['checkbox'], [
                    "checkbox_name" => "",
                    "content" => str_replace($attributes, $get_provider_data, $checkbox->content),
                    "id" => $checkbox->id,
                    "required" => $checkbox->checkbox_required,
                    "validation_message" => $checkbox->validation_message
                ]);
            }
        } else {
            $provider_state_eic = ProviderContent::with('checkbox')->where([
                'provider_id' =>  $provider_id,
                'state_id' => 0,
                'provider_contents.type' => '14',
                'provider_contents.service_type' => $request->service_id,
                'provider_contents.status' => 1,
            ])->first();
            if ($provider_state_eic) {
                $response['content'] = str_replace($attributes, $get_provider_data, $provider_state_eic->description);
                $response['checkbox'] = [];
                foreach ($provider_state_eic->checkbox as $checkbox) {
                    array_push($response['checkbox'], [
                        "checkbox_name" => "",
                        "content" => str_replace($attributes, $get_provider_data, $checkbox->content),
                        "id" => $checkbox->id,
                        "required" => $checkbox->checkbox_required,
                        "validation_message" => $checkbox->validation_message
                    ]);
                }
            }
        }
        return $response;
    }

    static function getMobileProviderEic($request, $provider_id)
    {
        // $state = isset($request->state) ? $request->state : 0;
        // $state_id = DB::table('provider_states')->where('name', $state)->pluck('id')->first();
        $provider_content = ProviderContent::with('checkbox')->where([
            'provider_id' =>  $provider_id,
            'status' => 1,
        ])->whereIn('type', ['1', '2', '3', '4', '5', '6', '7', '8', '9', '17'])->get();

        $response = [];
        // $response['acknowledgement']  = $provider_content->where('type', '14')->first();
        if ($provider_content) {
            foreach ($provider_content as $key =>  $contents) {

                $object1  =  new \stdClass();
                $object1->id = $contents->id;
                $object1->title = $contents->title;
                $object1->description = $contents->description;
                $object1->checkbox = $contents->checkbox;
                if ($contents->type != 17) {
                    $response['eic_content'][$key] = $object1;
                } else {
                    $response['acknowledgment_content'][$key] = $object1;
                }
                //$response['content'][$key] = str_replace($attributes, $get_provider_data, $contents->description);
                // $response['checkbox'] = [];
                // foreach ($contents->checkbox as $index => $checkbox) {
                //     array_push($response['checkbox'], [
                //         "checkbox_name" => "",
                //         "content" => str_replace($attributes, $get_provider_data, $checkbox->content),
                //         "id" => $checkbox->id,
                //         "required" => $checkbox->checkbox_required,
                //         "validation_message" => $checkbox->validation_message
                //     ]);
                //     //$object1->checkbox = $checkbox;
                // } 
            }
        }
        return $response;
    }

    static function getMobileEic($request)
    {
        try {
            $response = [];
            $provider = Provider::select('id', 'name', 'user_id')->with('user.getUserAddress')->where('user_id', $request->provider_id)->first();
            if (!$provider) return null;
            // $response['mobile']['provider_name'] =  $provider->name;
            $company = DB::table('affiliates')->select('company_name')->where('user_id',Auth::user()->id)->first();
            $get_provider_data['affiliate_name'] = $company->company_name;
            $response['provider_name'] = $provider->name;
            $response['phone'] = decryptGdprData($provider->user->phone);
            $response['address'] = $provider->user->getUserAddress->address;
            $response['email'] =  decryptGdprData($provider->user->email);
            $attributes = ['@Affiliate-Name@', '@Provider-Name@', '@Provider-Phone-Number@', '@Provider-Address@', '@Provider-Email@'];
            $data = self::getMobileProviderEic($request, $provider->user_id);
            $response['eic_content'] = isset($data['eic_content']) ? $data['eic_content'] : '';
            $response['acknowledgment_content'] = isset($data['acknowledgment_content']) ? $data['acknowledgment_content'] : '';


            return $response;
        } catch (\Exception $e) {
            // return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002);
            return errorResponse($e->getMessage() . " on line:" . $e->getLine() . ' file: ' . $e->getFile(), $e->getCode(), OTP_ERROR_CODE, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(31-March-2022)
     * save utm parameter of broadband
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    static public function saveSatelliteQuestion($request)
    {
        try {
            if ($request->header("ServiceID") == 3) {
                $visit_id = decryptGdprData($request->visit_id);
                if ($visit_id) {
                    $data = SaleProductsBroadband::where('lead_id', $visit_id)->first();
                    if ($data) {
                        $response = SaleProductsBroadband::where('lead_id', $visit_id)->update([
                            'power_type'    => $request->power_type,
                            'building_type' => $request->building_type,
                            'roof_type'     => $request->roof_type,
                            'wall_type'     => $request->wall_type
                        ]);

                        if ($response) {
                            return successResponse("Data has been saved successfully", 2001);
                        }
                        return errorResponse("Something went wrong", HTTP_STATUS_SERVER_ERROR, 2002);
                    }
                    return errorResponse("Visit id is not found", HTTP_STATUS_SERVER_ERROR, 2002);
                }
                return errorResponse("Something went wrongs", HTTP_STATUS_SERVER_ERROR, 2002);
            }
            return errorResponse("This api is not used for this service", HTTP_STATUS_SERVER_ERROR, 2002);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

    /**
     *get a listing of the broadband plans.
     *
     * @return Array
     */
    public static function getPlanList($request)
    {
        try {
            $request_array = $request->all();
            $connectionType = $request->input('connection_type');
            $serviceId = $request->header('serviceid');
            $tech_type = $request->input('technology_name');

            $api_key = encryptGdprData($request->header('api-key'));
            $affiliate_id = AffiliateKeys::where('api_key', $api_key)->pluck('user_id')->first();
            $assign_providers = AssignedUsers::where('service_id', $serviceId)->where('source_user_id', $affiliate_id)->where('relation_type', 1)->pluck('relational_user_id')->toArray();
            //genrate pl/pd token
            $request_encode_json = json_encode($request_array);
            // $encrypted_remarketing_token = set_encrypt_data($request_encode_json);

            // $remarketing_data['remarketing_token'] = $encrypted_remarketing_token;

            $plans = PlansBroadband::where('status', '1')->where('connection_type', $connectionType)->with('providers', 'providers.providerLogo', 'technologies', 'contracts', 'planfees');
            $connections = $technology = ConnectionType::where('status', '1')->where('is_deleted', '0');
            $connection_name = $connections->where('id', $connectionType)->pluck('name');
            if ($tech_type != false) {
                $tech_type_id = $technology->where('name', '=', $tech_type)->where('status', '1')->where('is_deleted', '0')->pluck('id');
                if (isset($tech_type_id) && count($tech_type_id) > 0) {
                    $plans = $plans->whereHas('technologies', function ($q) use ($tech_type_id) {
                        $q->where('technology_id', $tech_type_id[0]);
                    });
                }
            }
            $plans = $plans->whereHas('providers', function ($q) use ($assign_providers) {
                return $q->where('status', 1)->whereIn('user_id', $assign_providers);
            });

            $plans = $plans->get();
            if (count($plans) > 0) {
                $basicPlanDetail = [];
                foreach ($plans as $key => $planData) {
                    $basicPlanDetail[$key]['plan_id'] = isset($planData->id) ? $planData->id : '';
                    $basicPlanDetail[$key]['plan_name'] = isset($planData->name) ? $planData->name : '';
                    $basicPlanDetail[$key]['plan_nbn_key_url'] = isset($planData->nbn_key_url) ? $planData->nbn_key_url : '';
                    $basicPlanDetail[$key]['inclusion'] = isset($planData->inclusion) ? $planData->inclusion : '';
                    $basicPlanDetail[$key]['cost'] = isset($planData->plan_cost) ? $planData->plan_cost : '';
                    // $basicPlanDetail[$key]['plan_cost_name'] = isset($planData['plan_cost_type'][$key]['cost_name']) ? $planData['plan_cost_type'][$key]['cost_name'] : '';
                    $basicPlanDetail[$key]['cost_description'] = isset($planData->plan_cost_description) ? $planData->plan_cost_description : '';
                    $basicPlanDetail[$key]['special_offer'] = isset($planData->special_offer) ? $planData->special_offer : '';
                    $basicPlanDetail[$key]['special_offer_price'] = isset($planData->special_offer_price) ? $planData->special_offer_price : '';

                    // $basicPlanDetail[$key]['special_cost_name'] = isset($planData['special_cost_type'][$key]['cost_name']) ? $planData['special_cost_type'][$key]['cost_name'] : '';

                    $basicPlanDetail[$key]['special_offer_status'] = isset($planData->special_offer_status) ? $planData->special_offer_status : '';
                    $basicPlanDetail[$key]['internet_speed'] = isset($planData->internet_speed) ? $planData->internet_speed : '';
                    $basicPlanDetail[$key]['satellite_inclusion'] = isset($planData->satellite_inclusion) ? $planData->satellite_inclusion : 'N/A';
                    $basicPlanDetail[$key]['connection_type_info'] = isset($planData->connection_type_info) ? $planData->connection_type_info : '';
                    $basicPlanDetail[$key]['internet_speed_info'] = isset($planData->internet_speed_info) ? $planData->internet_speed_info : '';
                    $basicPlanDetail[$key]['plan_cost_info'] = isset($planData->plan_cost_info) ? $planData->plan_cost_info : '';
                    $basicPlanDetail[$key]['connection_name'] = isset($connection_name[0]) ? $connection_name[0] : '';
                    /*********Plan Description*****/
                    // $basicPlanDetail[$key]['plan_description'] = isset($planData['contents']['description']) ? $planData['contents']['description'] : '';
                    /*********Contract Details****/
                    $basicPlanDetail[$key]['contract_name'] = isset($planData['contracts']['contract_name']) ? $planData['contracts']['contract_name'] : '';
                    $basicPlanDetail[$key]['contact_duration'] = isset($planData['contracts']['validity']) ? $planData['contracts']['validity'] : '';
                    $basicPlanDetail[$key]['contract_description'] = isset($planData['contracts'][' description']) ? $planData['contracts']['description'] : '';
                    /********Provider ************/
                    $basicPlanDetail[$key]['provider_name'] = isset($planData['providers']['name']) ? $planData['providers']['name'] : '';
                    $basicPlanDetail[$key]['provider_id'] = isset($planData->provider_id) ? $planData->provider_id : '';

                    if (isset($planData->provider_id)) {
                        if ($planData['providers']['providerLogo']) {
                            $basicPlanDetail[$key]['provider_logo_name'] = isset($planData['providers']['providerLogo']->name) ? $planData['providers']['providerLogo']->name : '';
                            $basicPlanDetail[$key]['logo_url'] = isset($planData['providers']['providerLogo']->url) ? $planData['providers']['providerLogo']->url : '';
                        }
                    }
                    /********Plan Data ***/
                    $basicPlanDetail[$key]['total_allowance'] = isset($planData->total_data_allowance) ? $planData->total_data_allowance : '';
                    $basicPlanDetail[$key]['plan_related_data'] = isset($planData->off_peak_data) ? $planData->off_peak_data : '';
                    $basicPlanDetail[$key]['peak_data'] = isset($planData->peak_data) ? $planData->peak_data : '';
                    /********Plan Information ***/
                    $basicPlanDetail[$key]['technology'] = isset($request->technology_name) ? $request->technology_name : '';
                    $basicPlanDetail[$key]['download_speed'] = isset($planData->download_speed) ? $planData->download_speed : '';
                    $basicPlanDetail[$key]['upload_speed'] = isset($planData->upload_speed) ? $planData->upload_speed : '';
                    $basicPlanDetail[$key]['speed_description'] = isset($planData->speed_description) ? $planData->speed_description : '';
                    $basicPlanDetail[$key]['typical_peak_time_download_speed'] = isset($planData->typical_peak_time_download_speed) ? $planData->typical_peak_time_download_speed : '';
                    $arrDataLimit = config('plans.data_limit');
                    $basicPlanDetail[$key]['data_limit'] =  isset($planData->data_limit) ? (isset($arrDataLimit[$planData->data_limit]) ?? '') : '';
                    //$basicPlanDetail[$key]['data_limit'] = isset($planData->data_limit) ? $planData->data_limit.' GB' : '';
                    /*********Plan Fees ****/
                    $basicPlanDetail[$key]['plan_fees'] = isset($planData->planfees) ? $planData->planfees->select('fees', 'fee_id', 'cost_type_id')->get()->toArray() : '';
                    // $basicPlanDetail[$key]['monthly_cost'] = isset($planData->fees['monthly_cost']) ? $planData->fees['monthly_cost'] :'';
                    // $basicPlanDetail[$key]['minimum_cost'] = isset($planData->fees['minimum_total_cost']) ? $planData->fees['minimum_total_cost'] : '';
                    // $basicPlanDetail[$key]['setup_cost'] = isset($planData->fees['setup_fee']) ? $planData->fees['setup_fee'] :'';
                    // $basicPlanDetail[$key]['delivery_cost'] = isset($planData->fees['delivery_fee']) ? $planData->fees['delivery_fee'] : '';
                    // $basicPlanDetail[$key]['modem_cost'] = isset($planData->fees['modem_cost']) ? $planData->fees['modem_cost'] : '';
                    // $basicPlanDetail[$key]['modem_description'] = isset($planData->fees['modem_description']) ? $planData->fees['modem_description'] : '';
                    // $basicPlanDetail[$key]['processing_fee'] = isset($planData->fees['payment_processing_fees']) ? $planData->fees['payment_processing_fees'] : '';
                    // $basicPlanDetail[$key]['fees_charges'] = isset($planData->fees['other_fee_and_charges']) ? $planData->fees['other_fee_and_charges'] :'';

                }
                if (count($basicPlanDetail) > 0) {
                    $planCount = count($basicPlanDetail);
                } else {
                    $planCount = 0;
                }
                // $planRelatedData = [
                //     'plan_count' => $planCount,
                // ];

                $finalData = [
                    'plans' => $basicPlanDetail,
                    'count' => $planCount,
                ];
            } else {
                $finalData = null;
            }
            if ($finalData != "" || $finalData != null) {
                $response = ['status' => true, 'message' => 'success.', 'response' => $finalData, 'status_code' => 200];
            } else {
                $response = ['status' => false, 'message' => 'Plan not found. Please try again later.', 'status_code' => 200];
            }
            return $response;
        } catch (\Exception $err) {
            throw $err;
        }
    }

    /**
     *get a listing of the broadband plans addons.
     *
     * @return Array
     */
    public static function saveJourneyData($request)
    {
        DB::beginTransaction();
        try {
            $data['lead_id'] = $request['visit_id'];
            $data['connection_type'] = $request['connection_type'];
            $data['technology_type'] = $request['technology_type'];
            $data['address'] = $request['address'];
            $data['movein_type'] = $request['movein_type'];
            if ($request['movein_type'] == 1) {
                $data['movein_date'] = $request['movein_date'];
            }
            LeadJourneyDataBroadband::updateOrCreate(['lead_id' => $request['visit_id']], $data);
            $response = ['status' => true, 'message' => 'success.', 'response' => 'Journey data successfully created.', 'status_code' => 200];
            DB::commit();
            return $response;
        } catch (\Exception $err) {
            DB::rollback();
            throw $err;
        }
    }

    /**
     *get a listing of the broadband plans addons.
     *
     * @return Array
     */
    public static function getPlanAddonList($request)
    {
        try {
            $plan_id = $request->input('plan_id');
            $homeConnectionData = [];
            $planAddonsModemData = [];
            $planOtherAddonsData = [];
            $modemTechId = '';
            $techName = $request->input('technology_name');
            $serviceId = $request->header('serviceid');
            // if(isset($techName) && $techName !='false'){
            //     $findTechName = ConnectionType::where('service_id','=',$serviceId)->where('name','=',$techName)->where('status','1')->where('is_deleted','0')->whereNull('name')->first();
            //     if(isset($findTechName->id)){
            //         $modemTechId = $findTechName->id;
            //     }
            // }
            $data = PlansBroadbandAddon::where('plan_id', $plan_id)->with(['masterAddon' => function ($q) {
                $q->with('cost_type');
                $q->where('status', '1');
            }])->get();
            foreach ($data as $key => $value) {
                if (!empty($value->masterAddon['id']) && $value->category == 3) {
                    $homeConnectionData[$key]['id'] = isset($value->masterAddon['id']) ? $value->masterAddon['id'] : '';
                    $homeConnectionData[$key]['call_plan_name'] = isset($value->masterAddon['name']) ? $value->masterAddon['name'] : '';
                    $homeConnectionData[$key]['call_plan_inclusion'] = isset($value->masterAddon['inclusion']) ? $value->masterAddon['inclusion'] : '';
                    $homeConnectionData[$key]['call_plan_cost'] = isset($value->masterAddon['cost']) ? $value->masterAddon['cost'] : '';
                    $homeConnectionData[$key]['call_cost_id'] = isset($value->masterAddon->cost_type->id) ? $value->masterAddon->cost_type->id : '';
                    $homeConnectionData[$key]['call_cost_name'] = isset($value->masterAddon->cost_type->cost_name) ? $value->masterAddon->cost_type->cost_name : '';
                    $homeConnectionData[$key]['call_cost_period'] = isset($value->masterAddon->cost_type->cost_period) ? $value->masterAddon->cost_type->cost_period : '';
                    $homeConnectionData[$key]['call_plan_detail'] = isset($value->masterAddon['description']) ? $value->masterAddon['description'] : '';
                    $homeConnectionData[$key]['provider_id'] = isset($value->masterAddon['provider_id']) ? $value->masterAddon['provider_id'] : '';
                    // if(isset($value->masterAddon['provider_id'])){
                    //     $providerId =  $value->masterAddon['provider_id'];
                    //     $providerLogo = ProviderLogo::where('provider_id',$providerId)->first();
                    //     if($providerLogo){
                    //               $homeConnectionData[$key]['provider_logo_name'] = isset($providerLogo->name) ? $providerLogo->name : 'N/A';
                    //               $homeConnectionData[$key]['provider_logo_url'] = isset($providerLogo->url) ? $providerLogo->url : 'N/A';  
                    //     }
                    // }else{
                    //      $homeConnectionData[$key]['provider_logo_name'] = 'N/A';
                    //      $homeConnectionData[$key]['provider_logo_url'] = 'N/A'; 
                    // }
                    $homeConnectionData[$key]['status'] = isset($value->masterAddon['status']) ? $value->masterAddon['status'] : '';
                    $homeConnectionData[$key]['order'] = isset($value->masterAddon['order']) ? $value->masterAddon['order'] : '';
                    $homeConnectionData[$key]['is_default'] = isset($value['is_default']) ? $value['is_default'] : '0';
                    $homeConnectionData[$key]['is_mandatory'] = isset($value['is_mandatory']) ? $value['is_mandatory'] : '0';
                    $homeConnectionData[$key]['show_status'] = isset($value['status']) ? $value['status'] : '0';
                    $homeConnectionData[$key]['show_selected'] = 0;
                    if ($request->has('source') && $request->input('source') == 2) {
                        $homeConnectionData[$key]['call_plan_script'] = isset($value->masterAddon['script']) ? $value->masterAddon['script'] : '';
                        if ((isset($value['is_default']) && $value['is_default']) == '1' || (isset($value['is_mandatory']) && $value['is_mandatory'] == '1')) {
                            $homeConnectionData[$key]['call_plan_script'] = isset($value['script']) ? $value['script'] : '';
                        }
                    }
                }
                if (!empty($value->masterAddon['id']) && $value->category == 4) {
                    $planAddonsModemData[$key]['id'] = isset($value->masterAddon['id']) ? $value->masterAddon['id'] : '';
                    $planAddonsModemData[$key]['modem_modal_name'] = isset($value->masterAddon['name']) ? $value->masterAddon['name'] : '';
                    $planAddonsModemData[$key]['modem_description'] = isset($value->masterAddon['description']) ? $value->masterAddon['description'] : '';
                    $planAddonsModemData[$key]['price'] = isset($value->cost) ? $value->cost : '';
                    $planAddonsModemData[$key]['broadband_modem_cost_type_id'] = isset($value->cost_type_id) ? $value->cost_type_id : '';
                    $planAddonsModemData[$key]['broadband_modem_cost_name'] = isset($value->masterAddon->cost_type->cost_name) ? $value->masterAddon->cost_type->cost_name : '';
                    $planAddonsModemData[$key]['broadband_modem_cost_period'] = isset($value->masterAddon->cost_type->cost_period) ? $value->masterAddon->cost_type->cost_period : '';
                    $planAddonsModemData[$key]['status'] = isset($value->masterAddon['status']) ? $value->masterAddon['status'] : '';
                    $planAddonsModemData[$key]['order'] = isset($value->masterAddon['order']) ? $value->masterAddon['order'] : '';
                    $planAddonsModemData[$key]['is_default'] = isset($value['is_default']) ? $value['is_default'] : '0';
                    $planAddonsModemData[$key]['is_mandatory'] = isset($value['is_mandatory']) ? $value['is_mandatory'] : '0';
                    if ($request->has('source') && $request->input('source') == 2) {
                        $planAddonsModemData[$key]['modem_script'] = isset($value['script']) ? $value['script'] : '';
                    }
                }
                if (!empty($value->masterAddon['id']) && $value->category == 5) {
                    $planOtherAddonsData[$key]['id'] = isset($value->masterAddon['id']) ? $value->masterAddon['id'] : '';
                    $planOtherAddonsData[$key]['addon_name'] = isset($value->masterAddon['name']) ? $value->masterAddon['name'] : '';
                    $planOtherAddonsData[$key]['addon_description'] = isset($value->masterAddon['description']) ? $value->masterAddon['description'] : '';
                    $planOtherAddonsData[$key]['price'] = isset($value->price) ? $value->price : '';
                    $planOtherAddonsData[$key]['addon_cost_id'] = isset($value->cost_type_id) ? $value->cost_type_id : '';
                    $planOtherAddonsData[$key]['addon_cost_name'] = isset($value->masterAddon->cost_type->cost_name) ? $value->masterAddon->cost_type->cost_name : '';
                    $planOtherAddonsData[$key]['addon_cost_period'] = isset($value->masterAddon->cost_type->cost_period) ? $value->masterAddon->cost_type->cost_period : '';
                    $planOtherAddonsData[$key]['status'] = isset($value->masterAddon['status']) ? $value->masterAddon['status'] : '';
                    $planOtherAddonsData[$key]['is_default'] = isset($value['is_default']) ? $value['is_default'] : '0';
                    $planOtherAddonsData[$key]['show_status'] = isset($value['status']) ? $value['status'] : '0';
                    $planOtherAddonsData[$key]['is_mandatory'] = isset($value['is_mandatory']) ? $value['is_mandatory'] : '0';
                    if ($request->has('source') && $request->input('source') == 2) {
                        $planOtherAddonsData[$key]['addon_script'] = isset($value['script']) ? $value['script'] : '';
                    }
                }
            }
            $is_boyo_modem = 0;
            $findPlan = PlansBroadband::find($plan_id);
            if ($findPlan)
                $is_boyo_modem = isset($findPlan->is_boyo_modem) ? $findPlan->is_boyo_modem : '0';

            if (!empty($homeConnectionData)) {
                foreach ($homeConnectionData as $key => $row) {
                    $price[$key] = $row['call_plan_cost'];
                }
                array_multisort($price, SORT_ASC, $homeConnectionData);
            }
            if (!empty($planAddonsModemData)) {
                foreach ($planAddonsModemData as $key => $row) {
                    $modemPrice[$key] = $row['price'];
                }
                array_multisort($modemPrice, SORT_ASC, $planAddonsModemData);
            }
            if (!empty($planOtherAddonsData)) {
                foreach ($planOtherAddonsData as $key => $row) {
                    $addonPrice[$key] = $row['price'];
                }
                array_multisort($addonPrice, SORT_ASC, $planOtherAddonsData);
            }

            $finalData = [
                'home_connection' => array_values($homeConnectionData),
                'is_boyo_modem' => $is_boyo_modem,
                'plan_addons_modem' => array_values($planAddonsModemData),
                'plan_other_addons' => array_values($planOtherAddonsData),
            ];
            if ($finalData) {
                $response = ['status' => true, 'message' => 'success.', 'response' => $finalData, 'status_code' => 200];
            } else {
                $response = ['status' => false, 'message' => 'not found. Please try again later.', 'status_code' => 400];
            }
            return $response;
        } catch (\Exception $err) {
            throw $err;
        }
    }
    /**
     * get master details.
     * @return \Illuminate\Http\Response
     */
    static public function getMasterDetails($request)
    {
        try {
            $data = [];
            $cacheKey = self::getCacheKey($request, 'master:details:');
            $cacheData = self::getDataFromCache($cacheKey);
            if ($cacheData) {
                $data = $cacheData;
            } else {
                
                $data['unit_type_codes'] = config('master_data.unit_type_codes');
                $master = DB::table('master_employment_details')->get()->toArray();
                $data['employment_status'] = [];
                $data['industry_type'] = [];
                $data['occupation_type'] = [];
                $data['active_occupation_type'] = [];
                $data['residential_status'] = [];
                $data['unit_type_codes'] = [];
                foreach ($master as $row) {
                    if ($row->type == 1) {
                        array_push($data['employment_status'], [
                            'id' => $row->id,
                            'name' => $row->name
                        ]);
                    }
                    if ($row->type == 2) {
                        array_push($data['industry_type'], [
                            "id" => $row->id,
                            'name' => $row->name
                        ]);
                    }
                    if ($row->type == 3 || $row->type == 4) {
                        array_push($data['occupation_type'], [
                            'id' => $row->id,
                            'name' => $row->name
                        ]);
                    }
                    if ($row->type == 4) {
                        array_push($data['active_occupation_type'], [
                            'id' => $row->id,
                            'name' => $row->name
                        ]);
                    }
                    if ($row->type == 5) {
                        array_push($data['residential_status'], [
                            'id' => $row->id,
                            'name' => $row->name
                        ]);
                    }
                    if ($row->type == 6) {
                        array_push($data['unit_type_codes'], [
                            'id' => $row->id,
                            'name' => $row->name
                        ]);
                    }
                }
                $data['street_codes'] = DB::table('master_street_codes')->get()->toArray();
                $data['dl_states'] = DB::table('states')->select('state_id', 'state_code as name')->get()->toArray();
                if (!$request->header('serviceId') || $request->header('serviceId') == 2) {
                    $data['current_provider_list'] = DB::table('connection_types')->select('id','local_id','name')->where([
                        'service_id'        =>2,
                        'connection_type_id' =>3
                    ])->get()->toArray();
                }
                $data['concession_options'] = DB::table('connection_types')->select('id','local_id','name')->where([
                    'service_id'        =>1,
                    'connection_type_id' =>1
                ])->get()->toArray();
                
                self::addDataIntoCache($cacheKey, $data);
            }
            
            
            
            // if ($request->header('serviceId') == 1) {
            //     $visitors_data = Lead::select('sale_products_energy.product_type', 'sale_products_energy.provider_id', 'visitors.*', 'providers.name')->join('sale_products_energy', 'leads.lead_id', 'sale_products_energy.lead_id')->join('visitors', 'visitors.id', 'leads.visitor_id')->leftJoin('providers', 'sale_products_energy.provider_id', 'providers.user_id')->where([
            //         'leads.lead_id' => decryptGdprData($request->visit_id)
            //     ])->first();
            // } else if ($request->header('serviceId') == 2) {
            //     $visitors_data = Lead::select('sale_products_mobile.product_type', 'sale_products_mobile.provider_id', 'visitors.*', 'providers.name', 'lead_journey_data_mobile.connection_type')->join('sale_products_mobile', 'leads.lead_id', 'sale_products_mobile.lead_id')->join('visitors', 'visitors.id', 'leads.visitor_id')->leftJoin('providers', 'sale_products_mobile.provider_id', 'providers.user_id')->leftjoin('lead_journey_data_mobile', 'leads.lead_id', 'lead_journey_data_mobile.lead_id')->where([
            //         'leads.lead_id' => decryptGdprData($request->visit_id)
            //     ])->first();
            // } else {
            //     $visitors_data = Lead::select('sale_products_broadband.product_type', 'sale_products_broadband.provider_id', 'visitors.*', 'providers.name', 'lead_journey_data_broadband.connection_type')->join('sale_products_broadband', 'leads.lead_id', 'sale_products_broadband.lead_id')->join('visitors', 'visitors.id', 'leads.visitor_id')->leftJoin('providers', 'sale_products_broadband.provider_id', 'providers.user_id')->leftjoin('lead_journey_data_broadband', 'leads.lead_id', 'lead_journey_data_broadband.lead_id')->where([
            //         'leads.lead_id' => decryptGdprData($request->visit_id)
            //     ])->first();
            // }
            //  "visit_id":"cnJLRjV6UHJTd3FHaGxvMmZCUFg1Zz09"
            $visitors_data = Lead::select('leads.lead_id', 'leads.visitor_id', 'leads.connection_address_id', 'leads.billing_address_id', 'leads.delivery_address_id','billing_preference','delivery_preference','australia_resident_status')->with(['visitor' => function ($q) {
                $q->select('id', 'title', 'first_name', 'middle_name', 'last_name', 'email', 'dob', 'phone', 'alternate_phone');
            },'visitorIdentification','visitorEmployement','visitorPreviousEmployement','visitorCurrentAddress','visitorPreviousAddress','visitorBillingAddress','visitorDeliveryAddress'
            , 'energy' => function ($q) {
                $q->select('product_type', 'provider_id', 'id', 'lead_id');
            }, 'energy.provider' => function ($q) {
                $q->select('user_id', 'name');
            }, 'broadband' => function ($q) {
                $q->select('product_type', 'provider_id', 'id', 'lead_id');
            }, 'broadband.provider' => function ($q) {
                $q->select('user_id', 'name');
            }, 'mobile' => function ($q) {
                $q->select('product_type', 'provider_id', 'id', 'lead_id', 'plan_id','sim_type');
            }, 'mobile.planMobile' => function ($q) {
                $q->select('id', 'name', 'sim_type');
            }, 'mobile.mobileConnection', 
               'mobile.provider' => function ($q) {
                $q->select('user_id', 'name');
            }, 'mobile_lead_jounery' => function ($q) {
                $q->select('lead_id', 'current_provider');
            }, 'broadband_lead_jounery' => function ($q) {
                $q->select('lead_id', 'connection_type');
            }])->where([
                'leads.lead_id' => decryptGdprData($request->visit_id)
            ])->get(); //dd(decryptGdprData($request->visit_id));
            //  return $visitors_data;
            $data['visitors'] = [];
            $data['visitors']['provider_info']['boradband'] = [];
            $data['visitors']['provider_info']['mobile']    = [];
            $data['visitors']['provider_info']['energy']    = [];


            foreach ($visitors_data as $row) {
                $source = $row->visitor->dob;
                $dob = '';
                if (isset($source) && !empty($source)) {
                    $date = new \DateTime($source);
                    $dob = $date->format('d/m/Y');
                }
                $data['visitors'] = [
                    'title' => $row->visitor->title??'',
                    'first_name'   => $row->visitor->first_name,
                    'middle_name' => $row->visitor->middle_name??'',
                    'last_name' => $row->visitor->last_name,
                    'email' => $row->visitor->email,
                    'dob' => $dob,
                    'phone' => $row->visitor->phone,
                    'alternate_phone' => isset($row->visitor->alternate_phone) ? $row->visitor->alternate_phone : "",
                    'billing_preference' => $row->billing_preference??'',
                    'delivery_preference' => $row->delivery_preference??'',
                    'australia_resident_status' => $row->australia_resident_status??''
                ];
                if ($row->broadband) {
                    $data['visitors']['provider_info']['boradband'] = [
                        'product_type'    => isset($row->broadband) ? $row->broadband : '',
                        'connection_type' => isset($row->broadband_lead_jounery->connection_type) ? $row->broadband_lead_jounery->connection_type : ''
                    ];
                }
                if ($row->energy) {
                    $data['visitors']['provider_info']['energy'] = [
                        'product_type'    => isset($row->energy) ? $row->energy : '',
                    ];
                }
                if ($row->mobile) {
                    $data['visitors']['provider_info']['mobile'] = [
                        'product_type'    =>   isset($row->mobile) ? $row->mobile : '',
                        'current_provider' =>  isset($row->mobile_lead_jounery->current_provider) ? $row->mobile_lead_jounery->current_provider : '',
                        'connection_details'    =>   isset($row->mobile->mobileConnection) ? $row->mobile->mobileConnection : '',
                        'identification_details'    =>   isset($row->visitorIdentification) ? $row->visitorIdentification : '',
                        'employement_details'    =>   isset($row->visitorEmployement) ? $row->visitorEmployement : '',
                        'prev_employement_details'    =>   isset($row->visitorPreviousEmployement) ? $row->visitorPreviousEmployement : '',
                        'address_details'    =>   isset($row->visitorCurrentAddress) ? $row->visitorCurrentAddress : '',
                        'previous_address'    =>   isset($row->visitorPreviousAddress) ? $row->visitorPreviousAddress : '',
                        'billing_address'    =>   isset($row->visitorBillingAddress) ? $row->visitorBillingAddress : '',
                        'delivery_address'    =>   isset($row->visitorDeliveryAddress) ? $row->visitorDeliveryAddress : '',
                        'bank_info'    =>   isset($row->VisitorBankInfo) ? $row->VisitorBankInfo : '',
                        'debit_info'    =>   isset($row->VisitorDebitInfo) ? $row->VisitorDebitInfo : '',
                    ];
                }
            }
            return successResponse("Data fetched successfully", 2001, $data);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }


    static function checkPlanExists($request)
    {
        return  Self::where('id', $request->plan_id)->with([

            'provider'

        ])->first();
    }
}
