<?php

namespace App\Http\Requests\Broadband;


class BroadbandCommonRequest
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

        $rules = [
            'service_id' => 'required',
            'address' => 'required',
        ];




        return $rules;
    }

    public function messages()
    {
        $message = array();
        $message['service_id.required'] = 'Service id is required';
        $message['address.required'] = 'Address is required';



        return $message;
    }
}
