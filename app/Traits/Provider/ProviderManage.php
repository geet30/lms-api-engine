<?php

namespace App\Traits\Provider;

use Illuminate\Support\Facades\DB;
use App\Models\{Provider, ProviderSections, Lead, PlanEnergy, SaleProductsEnergy, ProviderMoveIn, Affiliate, Distributor, EnergyContentAttribute, ProviderContent,ProviderPermission};
use Illuminate\Support\Facades\Auth;


trait ProviderManage
{
    protected static $lead;

    /**
     * Get Services or Single Service.
     * @param string  planId
     * @return object keyData
     */

    static function getProviderManageSection($request)
    {
        $data = [];
        $response = [];
        $providerIds = $request->provider_id;
        $serviceId = $request->header('ServiceId');
        $masterTabManageSection = config('app.master_tab_manage_section');
        $masterPersonalDetails = config('app.personal_details');
        $masterConnectionDetails = config('app.connection_details');
        $masterIdentificationDetails = config('app.identification_details');
        $masterEmployDetails = config('app.employment_details');
        $masterConnectionAdd = config('app.connection_address');
        $masterBillingDeliveryAd = config('app.billing_and_delivery_address');
        $master_mutli_identification_details = config('app.multi_identification_details');
        $data['section'] = [];
        if (isset($providerIds)) {
            $providerData = Provider::with(['user', 'ProviderDirectDebit', 'ProviderDirectDebit.providerContentCheckbox'])->where('user_id', $providerIds)->where('is_deleted', '0')->first();
            if ($providerData != "") {
                $manageableSection = ProviderSections::with(['section_options', 'section_options.section_sub_options'])->where('user_id', $providerIds)->where('service_id', $providerData['service_id'])->where('section_status', 1)->get();
                // echo "<pre>";dd($manageableSection->toArray());die;
                foreach ($manageableSection as $key => $sectionInfo) {
                    $data['section'][$key]['section_identifier'] = $section_id = isset($sectionInfo->section_id) ? $sectionInfo->section_id : '';
                    $sectionName = isset($masterTabManageSection[$section_id]) ? $masterTabManageSection[$section_id] : '';
                    $data['section'][$key]['section_name'] = $sectionName;

                    $data['section'][$key]['section_acknowledgement'] = isset($sectionInfo->acknowledgement) ? $sectionInfo->acknowledgement : '';

                    foreach ($sectionInfo->section_options as $index => $manageSectionOptns) {
                        $data['section'][$key]['section_option'][$index]['option_identifier'] = $optionId = isset($manageSectionOptns->section_option_id) ? $manageSectionOptns->section_option_id : '';
                        if ($sectionName == "personal_details") {
                            $optionName = $masterPersonalDetails[$optionId];
                        }
                        if ($sectionName == "connection_details") {
                            $optionName = $masterConnectionDetails[$optionId];
                        }
                        if ($sectionName == "identification_details") {
                            $data['section'][$key]['section_option'][$index]['option_identifier_option'] = isset($manageSectionOptns->is_required) ? $manageSectionOptns->is_required : '';
                            $optionName = $masterIdentificationDetails[$optionId];
                            if ($optionId == 1) {
                                $optionName = 'Primary Identification';
                            }
                            if ($optionId == 2) {
                                $optionName = 'Secondary Identification';
                            }
                        }
                        if ($sectionName == "employment_details") {
                            $optionName = $masterEmployDetails[$optionId];
                        }
                        if ($sectionName == "connection_address") {
                            $optionName = $masterConnectionAdd[$optionId];
                        }
                        if ($sectionName == "billing_and_delivery_address") {
                            if ($optionId == 1) {
                                $optionName = 'Billing Address';
                            }
                            if ($optionId == 2) {
                                $optionName = 'Delivery Address';
                            }
                        }
                        $secOptionName = isset($optionName) ?  $optionName : '';
                        $data['section'][$key]['section_option'][$index]['option_name'] = $secOptionName;


                        // if($manageSectionOptns->min_value_limit != 0 && $secOptionName == 'Minimum time of employment'){
                        //     $data['section'][$key]['section_option'][$index]['min_limit'] = isset($manageSectionOptns->min_value_limit) ? $manageSectionOptns->min_value_limit : '';
                        // }
                        // if($manageSectionOptns->max_value_limit != 0 && $secOptionName == 'Minimum time of employment'){
                        //     $data['section'][$key]['section_option'][$index]['max_limit'] = isset($manageSectionOptns->max_value_limit) ? $manageSectionOptns->max_value_limit : '';
                        // }
                        if ((($section_id == 4 && $optionId == 4) || ($section_id == 5 && $optionId == 1)) && $manageSectionOptns->min_value_limit >= 0) {
                            $data['section'][$key]['section_option'][$index]['min_limit'] = isset($manageSectionOptns->min_value_limit) ? $manageSectionOptns->min_value_limit : '';
                        }
                        if ((($section_id == 4 && $optionId == 4) || ($section_id == 5 && $optionId == 1)) && $manageSectionOptns->max_value_limit >= 0) {
                            $data['section'][$key]['section_option'][$index]['max_limit'] = isset($manageSectionOptns->max_value_limit) ? $manageSectionOptns->max_value_limit : '';
                        }
                        if ($section_id == 3) {
                            $subOptionId = isset($manageSectionOptns->section_sub_options) ? $manageSectionOptns->section_sub_options : '';
                            if (isset($subOptionId)) {
                                $data['section'][$key]['section_option'][$index]['sub_option_identifier'] = $subOptionId;
                                foreach ($subOptionId as $key2 => $value) {
                                    $subOptionName = $master_mutli_identification_details[$optionId][$secOptionName][$subOptionId[$key2]->section_sub_option_id];
                                    $subOptionId[$key2]->section_sub_option_name = $subOptionName;
                                }
                            } else {
                                $data['section'][$key]['section_option'][$index]['sub_option_identifier'] = '';
                            }
                        }
                        if ($section_id == 6) {
                            $subOptionId = isset($manageSectionOptns->section_sub_options) ? $manageSectionOptns->section_sub_options : '';


                            if (isset($subOptionId)) {

                                $data['section'][$key]['section_option'][$index]['sub_option_identifier'] = $subOptionId;
                                foreach ($subOptionId as $key2 => $value) {

                                    $subOptionName = $masterBillingDeliveryAd[$optionId][$secOptionName][$subOptionId[$key2]['section_sub_option_id']];

                                    $subOptionId[$key2]['section_sub_option_name'] = $subOptionName;
                                }
                            } else {
                                $data['section'][$key]['section_option'][$index]['sub_option_identifier'] = '';
                            }
                        }
                    }
                }
                $data['direct_debit_section'] = 0;
                if (isset($providerData->ProviderDirectDebit) && $providerData->ProviderDirectDebit != null) {
                    $data['direct_debit_section'] = 0;
                    if (isset($providerData->ProviderDirectDebit->status) && $providerData->ProviderDirectDebit->status == 1) {
                        $data['direct_debit_section'] = 1;
                        $data['is_card_status'] = 0;
                        $data['is_bank_status'] = 0;
                        if ($providerData->ProviderDirectDebit->payment_method_type == 1) {
                            $data['is_card_status'] = 1;
                            $data['is_card_content'] = $providerData->ProviderDirectDebit->is_card_content;
                            if ($providerData->ProviderDirectDebit->is_card_content == 1) {
                                $data['card_information'] = isset($providerData->ProviderDirectDebit->card_information) ? $providerData->ProviderDirectDebit->card_information : '';
                            }
                        } else if ($providerData->ProviderDirectDebit->payment_method_type == 2) {
                            $data['is_bank_status'] = 1;
                            $data['is_bank_content'] = $providerData->ProviderDirectDebit->is_bank_content;
                            if ($providerData->ProviderDirectDebit->is_bank_content == 1) {
                                $data['bank_information'] = isset($providerData->ProviderDirectDebit->bank_information) ? $providerData->ProviderDirectDebit->bank_information : '';
                            }
                        } else if ($providerData->ProviderDirectDebit->payment_method_type == 3) {
                            $data['is_card_status'] = 1;
                            $data['is_card_content'] = $providerData->ProviderDirectDebit->is_card_content;
                            if ($providerData->ProviderDirectDebit->is_card_content == 1) {
                                $data['card_information'] = isset($providerData->ProviderDirectDebit->card_information) ? $providerData->ProviderDirectDebit->card_information : '';
                            }
                            $data['is_bank_status'] = 1;
                            $data['is_bank_content'] = $providerData->ProviderDirectDebit->is_bank_content;
                            if ($providerData->ProviderDirectDebit->is_bank_content == 1) {
                                $data['bank_information'] = isset($providerData->ProviderDirectDebit->bank_information) ? $providerData->ProviderDirectDebit->bank_information : '';
                            }
                        }
                    }
                }
                //$provider_checkbox = [];
                $data['provider_checkbox'] = [];
                $data['service_id'] = isset($providerData->service_id) ? $providerData->service_id : '';
                // $data['status'] = isset($providerData->ProviderDirectDebit->status) ? $providerData->ProviderDirectDebit->status : '';
                // $data['is_card_content'] = isset($providerData->ProviderDirectDebit->is_card_content) ? $providerData->ProviderDirectDebit->is_card_content : '';
                // $data['is_bank_content'] = isset($providerData->ProviderDirectDebit->is_bank_content) ? $providerData->ProviderDirectDebit->is_bank_content : '';
                // $data['card_information'] = isset($providerData->ProviderDirectDebit->card_information) ? $providerData->ProviderDirectDebit->card_information : '';
                // $data['bank_information'] = isset($providerData->ProviderDirectDebit->bank_information) ? $providerData->ProviderDirectDebit->bank_information : '';
                $attributes = ['@Affiliate-Name@', '@Provider-Name@', '@Provider-Phone-Number@', '@Provider-Address@', '@Provider-Email@', '@Customer-Name@', '@Customer-Email@', '@Customer-Phone@'];

                $provider_checkbox = isset($providerData->ProviderDirectDebit->providerContentCheckbox) ? $providerData->ProviderDirectDebit->providerContentCheckbox->toArray() : [];


                $leadId = decryptGdprData($request->visit_id);
                // $service = Lead::getService();
                $columns = ['visitors.first_name', 'visitors.last_name', 'visitors.phone', 'visitors.email','visitor_addresses.state',];
                $content_data = self::$lead = Lead::getFirstLead(
                    ['leads.lead_id' => $leadId],
                    $columns,
                    true,
                    true,
                    true,
                    null,
                    null,
                    true
                );
                $get_provider_data['affiliate_name'] = decryptGdprData(Auth::user()->first_name) . ' ' . decryptGdprData(Auth::user()->last_name);
                $get_provider_data['provider_name'] = isset($providerData->name) ? $providerData->name : '';
                $get_provider_data['provider_phone'] = isset($providerData->user->phone) ? decryptGdprData($providerData->user->phone) : '';
                $get_provider_data['provider_address'] = isset($providerData->getUserAddress->address) ? $providerData->getUserAddress->address : '';
                $get_provider_data['provider_email'] = isset($providerData->user->email) ? decryptGdprData($providerData->user->email) : '';
                $get_provider_data['customer_name'] = decryptGdprData($content_data->first_name) . ' ' . decryptGdprData($content_data->last_name);
                $get_provider_data['customer_phone'] = isset($content_data->phone) ? decryptGdprData($content_data->phone) : '';
                $get_provider_data['customer_email'] =  isset($content_data->email) ? decryptGdprData($content_data->email) : '';

                if (!empty($provider_checkbox)) {
                    foreach ($provider_checkbox as $checkbox) { //dump($checkbox['content']);
                        array_push($data['provider_checkbox'], [
                            "id" => $checkbox['id'],
                            "provider_content_id" => $checkbox['provider_content_id'],
                            "checkbox_required" => $checkbox['checkbox_required'],
                            "validation_message" => $checkbox['validation_message'],
                            "content" => str_replace($attributes, $get_provider_data, $checkbox['content']),
                            "status" => $checkbox['status'],
                            "type" => $checkbox['type'],
                            "order" => $checkbox['order']
                        ]);
                    }
                }
                //dd($attributes,$get_provider_data,$provider_checkbox);
               
                $data['section'] = array_values($data['section']);
                $data['is_concession'] = 0;
                $state = DB::table('states')->select('state_id')->where('state_code',$content_data->state)->first();
                if($state){
                    $concession = DB::table('provider_concession')->where([
                        'state_id'    => $state->state_id,
                        'provider_id' => $providerIds
                      ])->first();
                      if($concession){
                        $data['is_concession'] = 1;
                    }
                }
                $commonColumns = ['id','is_new_connection','is_port','is_retention','connection_script','port_script','recontract_script'];
                if($serviceId == 1){
                    $energyColumns = ['is_sclerosis','is_medical_cooling','sclerosis_title','medical_cooling_title'];
                    $commonColumns = array_merge($commonColumns, $energyColumns);
                }
                $data['permission_data'] =  ProviderPermission::with(['checkbox' => function ($qu){ $qu->where('type', 22)->whereNotNull('connection_type')->select('id','provider_content_id','checkbox_required','validation_message','content','order','connection_type'); }])->select($commonColumns)->where('user_id',$providerIds)->get();
                return $data;
            } else {
                return false;
            }
        }
        return $response;
    }

    static function getProviderMoveIn($requestData)
    {
        $visitId = decryptGdprData($requestData->visit_id);
        $reponse = [];
        $data = Lead::where('lead_id', $visitId)->with('energy_lead_jounery.billData')->first();
        if ($data) {
            $data = $data->toArray();
        } else {
            $respone['message'] = "First apply plan";
            $respone['status'] = false;
            $reponse['data'] = [];

            return $respone;
        }

        $planId = SaleProductsEnergy::where('lead_id', $visitId)->select('id', 'plan_id', 'provider_id')->get();

        if (!isset($planId[0]->plan_id)) {

            $respone['message'] = "First apply plan";
            $respone['status'] = false;
            $reponse['data'] = [];
            return $respone;
        }
        $userId = \Auth::user()->id;
        $attributes = EnergyContentAttribute::where('type', 3)->where('service_id', 1)->pluck('attribute');

        $affilatedata = Affiliate::where('user_id', $userId)->get();

        $dist = $data['energy_lead_jounery'][0]['distributor_id'];

        $energy = $data['energy_lead_jounery'][0]['energy_type'];

        $eleProvider = $planId[0]->provider_id;

        $tariff = isset($data['energy_lead_jounery'][0]['bill_data']['tariff_type']) ? $data['energy_lead_jounery'][0]['bill_data']['tariff_type'] : "peak_only";

        $distData = Distributor::where('id', $dist)->pluck('name');

        $providerPlanData = PlanEnergy::with([
            'rate' => function ($q) use ($tariff, $dist) {
                $q->where('distributor_id', $dist)->where('type', $tariff)->select('plan_id', 'connection_fee', 'disconnection_fee');
            }, 'provider'
        ])->where('id', $planId[0]->plan_id)->select('name', 'id', 'provider_id')->first();
        if ($data['energy_lead_jounery'][0]['is_dual'] == 1 && isset($data['energy_lead_jounery'][1])) {


            $gasEnergy = $data['energy_lead_jounery'][1]['energy_type'];

            $gasProvider = $planId[1]->provider_id;

            $gasdist = $data['energy_lead_jounery'][1]['distributor_id'];
            $gasDistData = Distributor::where('id', $gasdist)->pluck('name');
            //get gas plan data 
            $providerGasPlanData = PlanEnergy::with([
                'rate' => function ($q) use ($gasdist) {
                    $q->where('distributor_id', $gasdist)->select('plan_id', 'connection_fee', 'disconnection_fee');
                }, 'provider'
            ])->where('id', $planId[1]->plan_id)->select('name', 'id', 'provider_id')->first();

            $moveInGasData =  ProviderMoveIn::where('distributor_id', $gasdist)->where('property_type', $data['energy_lead_jounery'][1]['property_type'])->where('energy_type', $gasEnergy)->where('user_id', $gasProvider)->first();

            $gasreponse =  self::setContent($moveInGasData, $energy, $tariff, $providerGasPlanData, $data, $affilatedata, $gasDistData, $attributes);
            $respone['gas']['move_in_content'] =  $gasreponse['content'];
            $respone['gas']['move_in_eic_content'] =  $gasreponse['eic'];
            $respone['status'] = true;
        }

        $moveInData =  ProviderMoveIn::where('distributor_id', $dist)->where('property_type', $data['energy_lead_jounery'][0]['property_type'])->where('energy_type', $energy)->where('user_id', $eleProvider)->first();

        if ($moveInData == null) {
            $respone['message'] = " Move-in content not found";
            $respone['status'] = false;
            $respone['data'] = [];
            return $respone;
        }
        $elecreponse =  self::setContent($moveInData, $energy, $tariff, $providerPlanData, $data, $affilatedata, $distData, $attributes);

        $respone['electricity']['move_in_content'] =  $elecreponse['content'];
        $respone['electricity']['move_in_eic_content'] =  $elecreponse['eic'];
        $respone['status'] = true;

        return  $respone;
    }

    static function setContent($moveInData, $energy, $tariff, $providerPlanData, $visitData, $affilatedata, $distData, $attributes)
    {
        $parameters = [];
        $moveInDataContent = [];

        $parameter = str_replace(" ", "", $providerPlanData->name);
        $parameters['provider_name'] = $providerPlanData['provider']['name'];
        $parameters['provider_name'] = $providerPlanData['provider']['name'];
        $parameters['plan_name'] = $providerPlanData->plan_name;
        $parameters['provider_email'] = $providerPlanData['provider']->support_email;
        //  $parameters['provider_term_and_condition'] = $affilatedata->page_url . '/provider-term-conditions?provider=' . $parameter;
        $parameters['connection_fee'] = $providerPlanData['rate'][0]->connection_fee;
        $parameters['disconnection_fee'] = $providerPlanData['rate'][0]->disconnection_fee;
        $parameters['disconnection_fee'] = $providerPlanData['rate'][0]->disconnection_fee;
        $parameters['distributor_name'] = $distData[0];

        if ($moveInData->move_in_content_status == 1) {
            $moveInDataContent['content'] =  str_replace($attributes->toArray(), $parameters, $moveInData->move_in_content);
        } else {
            $moveInDataContent['content']  = '';
        }
        if ($moveInData->move_in_eic_content_status == 1) {
            $moveInDataContent['eic'] =  str_replace($attributes->toArray(), $parameters, $moveInData->move_in_eic_content);
        } else {
            $moveInDataContent['eic']  = '';
        }

        return $moveInDataContent;
    }
}
