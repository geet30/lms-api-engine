<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class VisitIdRequest
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
            'visit_id' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'visit_id.required' => 'Visit id required.'
        ];
    }
}
