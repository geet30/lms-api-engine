<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class ProviderListRequest
{

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
        return [
            "vertical"   => 'required',
            "assigned"   => 'required'
        ];
    }

    public function messages()
    {
        return [
            'assigned.required' => 'Assigned id required.',
            'vertical.required' => 'Vertical id required.'
        ];
    }
}
