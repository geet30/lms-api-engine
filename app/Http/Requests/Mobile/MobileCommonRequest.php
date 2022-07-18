<?php

namespace App\Http\Requests\Mobile;


class MobileCommonRequest
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
            'detail' => 'required',
        ];


        return $rules;
    }

    public function messages()
    {
        $message = array();
        $message['service_id.required'] = 'Service id is required';



        return $message;
    }
}
