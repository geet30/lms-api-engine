<?php

namespace App\Http\Requests\Common;

class VisitConnectionRequest
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
    public function rules()
    {
        $rules = array();
        $request = $this->request->all();
        $rules['visit_id'] = 'required';
        if ($request['service_id'] == 3) {

            if (isset($request['is_provider'])) {
                if ($request['is_provider'] == 1) {
                    $rules['provider_account'] = 'required|numeric';
                }
            }
            if (isset($request['existing_phone'])) {
                if ($request['existing_phone'] == 1) {
                    $rules['home_number'] = 'required|numeric';
                    $rules['current_account'] = 'required|numeric';
                    $rules['transfer_service'] = 'required';
                }
            }
        }
        if ($request["service_id"] == 2) {
            $rules['connection_type_request'] = 'required';
            if (isset($request['connection_type_request']) && $request['connection_type_request'] == 2 && !isset($request['conn_otp'])) {
                $rules['transfer_phone'] = ['required', 'numeric'];
                $rules['transfer_current_provider'] = 'required';
                $rules['transfer_account_number'] = 'required';
            } else if (isset($request['connection_type_request']) && $request['connection_type_request'] == 3 && !isset($request['conn_otp'])) {
                $rules['renew_account_number'] = 'required';

                if (isset($req['lease_detail']) && $req['lease_detail'] == 1) {
                    // if ($provider_id) {
                    //     $lease_date = ProviderSetting::where('provider_id', $provider_id)->value('lease_date');
                    // if ($lease_date != 0) {
                    //     $errors['lease_date'] = 'bail|required|date_format:d/m/Y|after_or_equal:' . date('d/m/Y', strtotime('-' . $lease_date . ' year')) . '| before_or_equal:' . date('d/m/Y');
                    // } else {
                    //     $errors['lease_date'] = 'bail|required|date_format:d/m/Y|before_or_equal:' . date('d/m/Y');
                    // }
                    //  }
                }
            }
        }

        return $rules;
    }

    public function messages()
    {
        $message = array();
        $request = $this->request->all();
        if ($request['service_id'] == 3) {
            $message['visit_id.required'] = 'Visit id is required.';
            $message['provider_account.required'] = 'Account Number is required.';
            $message['home_number.required'] = 'Please enter home phone number.';
            $message['home_number.numeric'] = 'Please enter a valid mobile number.';
            $message['current_account.required'] = 'Please enter account number.';
            $message['current_account.numeric'] = 'Please enter a valid account number.';
            $message['transfer_service.required'] = 'Transfer service is required';
        }
        if ($request['service_id'] == 2) {
            $message['connection_type_request.required'] = 'Request type is required.';
            $message['transfer_phone.required'] = 'Please enter mobile number.';
            $message['transfer_phone.numeric'] = 'Please enter a valid mobile number.';
            $message['transfer_current_provider.required'] = 'Please select current provider.';
            $message['transfer_account_number.required'] = 'Please enter account number.';
            $message['renew_account_number.required'] = 'Please enter account number.';
        }


        return $message;
    }
}
