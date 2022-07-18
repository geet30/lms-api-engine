<?php

namespace App\Traits\Plan;

use App\Models\{Lead, LeadOtp, PlanEnergy, PlanMobile, PlansBroadband, ProviderContent, Provider};

use Illuminate\Support\Facades\{DB};

/**
 * Plan Validation model.
 * Author: Sandeep Bangarh
 */

trait Validation
{
    static function eicRules()
    {
        $request = request();
        $saveCheckboxStatusData = $rules = $msgs = [];
        $data = ['saveCheckboxStatusData' => [], 'rules' => [], 'msgs' => []];
        $service = Lead::getService();
        foreach ($request->plan_id as $serviceId => $planId) {
            switch ($serviceId) {
                case 1:
                    $energyData = static::getEnergyEICRules($planId);
                    $data = static::setDataAndRules($energyData, $saveCheckboxStatusData, $rules, $msgs);
                    break;
                case 2:
                    $mobileData = static::getMobileEICRules($planId);
                    $data = static::setDataAndRules($mobileData, $saveCheckboxStatusData, $rules, $msgs);
                    break;
                case 3:
                    $broadbandData = static::getBraodbandEICRules($planId);
                    $data = static::setDataAndRules($broadbandData, $saveCheckboxStatusData, $rules, $msgs);
                    break;

                default:
                    # code...
                    break;
            }
            $saveCheckboxStatusData = $data['saveCheckboxStatusData'];
            if ($service == 'energy' && isset($data['saveCheckboxStatusData'][0])) {
                $saveCheckboxStatusData = $data['saveCheckboxStatusData'][0];
            }

            $rules = $data['rules'];
            $msgs = $data['msgs'];
        }
        return ['saveCheckboxStatusData' => $saveCheckboxStatusData, 'rules' => $rules, 'msgs' => $msgs];
    }

    static function eicMessages()
    {
    }

    static function setDataAndRules($data, $saveCheckboxStatusData, $rules, $msgs)
    {
        if ($data) {
            array_push($saveCheckboxStatusData, $data['saveCheckboxStatusData']);
            if (!empty($data['rules']))
                $rules = array_merge($rules, $data['rules']);

            if (!empty($data['msgs']))
                $msgs = array_merge($msgs, $data['msgs']);
        }
        return ['saveCheckboxStatusData' => $saveCheckboxStatusData, 'rules' => $rules, 'msgs' => $msgs];
    }

    static function getEnergyEICRules($planIds)
    {
        $request = request();
        if (!is_array($planIds)) {
            $planIds = [$planIds];
        }
        $data = [];
        $data['rules'] = [];
        $data['msgs'] = [];
        $plans = PlanEnergy::select('id', 'name', 'energy_type', 'provider_id')->with(['planEicContents.planEicContentCheckbox'])->whereIn('id', $planIds)->get();
        if (!$plans->isEmpty()) {
            foreach($plans as $plan) {
                if (!$plan->planEicContents->planEicContentCheckbox->isEmpty()) {
                    $checkboxes = $plan->planEicContents->planEicContentCheckbox;
                    $result = static::setContentAndRules($checkboxes, $plan, 'Plan EIC');
                    $data['saveCheckboxStatusData'][] = $result['saveCheckboxStatusData'];
                    $data['rules'] = array_merge($result['rules'], $data['rules']);
                    $data['msgs'] = array_merge($result['msgs'], $data['msgs']);
                }
        
                if ($plan->planEicContents->planEicContentCheckbox->isEmpty()) {
                    $providerData = static::getProviderEic($request, $plan);
                    if ($providerData) {
                        $data['rules'] = array_merge($providerData['rules'], $data['rules']);
                        $data['msgs'] = array_merge($providerData['msgs'], $data['msgs']);
                        $data['saveCheckboxStatusData'][] = $providerData['saveCheckboxStatusData'];
                    }
                    
                }
            }
            return $data;
        }

        return false;
    }

    static function getMobileEICRules($planId)
    {
        $plan = PlanMobile::select('id', 'name', 'provider_id')->find($planId);
        $providers = Provider::where('user_id',$plan->provider_id)->with(['acknowledgementContent.checkbox'])->first();
        return static::setMobileContentAndRules($providers, 'Mobile EIC');
    }

    static function getBraodbandEICRules($planId)
    {
        $plan = PlansBroadband::select('id', 'name', 'provider_id')->with(['planEicContentCheckbox' => function ($query) {
            $query->where('status', 1);
        }])->find($planId);
        return static::setContentAndRules($plan->planEicContentCheckbox, $plan, 'Broadband EIC');
    }

    static function getProviderEic($request, $plan)
    {
        $saveCheckboxStatusData = $rules = $msgs = [];
        $stateId = DB::table('provider_states')->where('name', $request->state)->pluck('id')->first();
        $providerStateEics = ProviderContent::with('checkbox')->where([
            'provider_id' =>  $plan->provider_id,
            'state_id' => $stateId,
            'provider_contents.type' => '14'
        ])->get();
        foreach ($providerStateEics as $providerStateEic) {
            if ($providerStateEic->save_checkbox_status == 1) {
                $myDataAdd['energy_type'] = $plan->energy_type;
                $myDataAdd['checkbox_source'] = 'Plan EIC';
                $myDataAdd['checkbox_id'] = $providerStateEic->id;
                $myDataAdd['checkbox_content'] = $providerStateEic->content;
                $myDataAdd['module_type'] = $providerStateEic->module_type;
                $myDataAdd['status'] = $request['checkbox_' . $providerStateEic->id] == 1 ? 1 : 0;
                $saveCheckboxStatusData = $myDataAdd;
            }

            if ($providerStateEic->required == 1) {
                $rules['checkbox_' . $providerStateEic->id] = 'required|integer|in:1';
                $msgs['checkbox_' . $providerStateEic->id . '.required'] = $providerStateEic->validation_message;
            }
        }
        if (empty($saveCheckboxStatusData)) return false;

        return ['saveCheckboxStatusData' => $saveCheckboxStatusData, 'rules' => $rules, 'msgs' => $msgs];
    }

    static function setContentAndRules($checkboxes, $plan, $source)
    {
        $saveCheckboxStatusData = $rules = $msgs = [];
        $request = request();

        foreach ($checkboxes as $checkbox) {
            if ($checkbox->save_checkbox_status == 1 || $checkbox->status == 1) {
                $myDataAdd['plan_id'] = $plan->id;
                if (isset($plan->energy_type)) {
                    $myDataAdd['energy_type'] = $plan->energy_type;
                }
                $myDataAdd['checkbox_source'] = $source;
                $myDataAdd['checkbox_id'] = $checkbox->id;
                $myDataAdd['checkbox_content'] = $checkbox->content;
                if (isset($checkbox->module_type))
                    $myDataAdd['module_type'] = $checkbox->module_type;
                $myDataAdd['status'] = $request['checkbox_' . $checkbox->id] == 1 ? 1 : 0;
                $saveCheckboxStatusData = $myDataAdd;
            }

            if ($checkbox->required == 1) {
                $rules['checkbox_' . $checkbox->id] = 'required|integer|in:1';
                $msgs['checkbox_' . $checkbox->id . '.required'] = $checkbox->validation_message;
            }
        }

        if (empty($saveCheckboxStatusData)) return false;

        return ['saveCheckboxStatusData' => $saveCheckboxStatusData, 'rules' => $rules, 'msgs' => $msgs];
    }

    static function setMobileContentAndRules($providers, $source)
    {
        $saveCheckboxStatusData = $rules = $msgs = [];
        $request = request();
        if(isset($providers->acknowledgementContent->checkbox)){
            foreach ($providers->acknowledgementContent->checkbox as $checkbox) {
                // if ($checkbox->save_checkbox_status == 1 || $checkbox->status == 1) {
                //     if (isset($plan->energy_type)) {
                //         $myDataAdd['energy_type'] = $plan->energy_type;
                //     }
                //     $myDataAdd['checkbox_source'] = $source;
                //     $myDataAdd['checkbox_id'] = $checkbox->id;
                //     $myDataAdd['checkbox_content'] = $checkbox->content;
                //     if (isset($checkbox->module_type))
                //         $myDataAdd['module_type'] = $checkbox->module_type;
                //     $myDataAdd['status'] = $request['checkbox_' . $checkbox->id] == 1 ? 1 : 0;
                //     $saveCheckboxStatusData = $myDataAdd;
                // }
    
                if ($checkbox->checkbox_required == 1) {
                    $rules['checkbox_'. $checkbox->id] = 'required|integer|in:1';
                    $msgs['checkbox_'. $checkbox->id .'.required'] = $checkbox->validation_message;
                }
            }
    
            // if (empty($saveCheckboxStatusData)) return false;
            return ['saveCheckboxStatusData' => $saveCheckboxStatusData, 'rules' => $rules, 'msgs' => $msgs];
        }
        

        
    }
}
