<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProviderSections;

class PersonalDetailsRequest
{

    function __construct($request)
	{
		$this->request = $request;
	}
    
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $required = 'bail|required|date_format:d/m/Y|before:-18 years';
        if($this->request->header('ServiceId') == 2 ||$this->request->header('ServiceId') == 3){
           $check =  self::checkSection($this->request);
           if(!$check){
            $required = 'bail|date_format:d/m/Y|before:-18 years';
           }
        }

        $rules = [
            'first_name'      => 'bail|required|min:2|max:50',
            'last_name'       => 'bail|required|min:2|max:50',
            'dob'             => $required,
            'alternate_phone' => 'bail|nullable|numeric|phone_custom|phone_length',
            'email'           => 'bail|required|email',
            'title'           => 'required',
            'phone'            => 'bail|required|numeric|phone_custom|phone_length',
            'visit_id'        => 'required',
        ];
        return $rules;
    }

    public function messages()
    {
        $messages = [
            'visit_id' => 'Visit id is required',
            'title.required' => 'Title is required',
            'email.required' => 'Email is required',
            'email.email' => 'Enter valid Email address',
            'first_name.required' => 'First name is required',
            'first_name.min' => 'Minimum two characters are required for first name',
            'first_name.max' => 'Maximum fifty characters are allowed for first name',
            'last_name.required' => 'Last name is required',
            'last_name.min' => 'Minimum two characters are required for last name',
            'last_name.max' => 'Maximum fifty characters are allowed for last name',
            'dob.required' => 'Please enter date of birth.',
            'dob.before' => 'You must be of minimum 18 years to apply for this plan.',
            'dob.date_format' => 'The format of date of birth should be DD/MM/YYYY.',
            'phone.required' => 'Phone is required',
            'phone.numeric' => 'Enter valid Mobile Number',
            'phone.phone_custom' => 'Mobile number must starts with 04',
            'phone.phone_length' => 'Mobile Number must be 10 digits long',
            'alternate_phone.numeric' => 'Enter valid Mobile Number',
            'alternate_phone.phone_custom' => 'Mobile number must starts with 04',
            'alternate_phone.phone_length' => 'Mobile Number must be 10 digits long',
        ];

        return $messages;
    }
    static public function checkSection($request){
        return $status = ProviderSections::select('provider_section_options.section_option_status')->join('provider_section_options','provider_sections.id','provider_section_options.provider_section_id')->where(
            [
                'provider_sections.service_id'                     => $request->header('ServiceId'),
                'provider_sections.section_id'                     => 1,
                'provider_sections.section_status'                 => 1,
                'provider_sections.user_id'                        => $request->provider_id,
                'provider_section_options.section_option_id'       => 5,
                'provider_section_options.section_option_status'   => 1,
            ]
        )->first();
     }
}
