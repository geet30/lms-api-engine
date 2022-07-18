<?php

namespace App\Http\Requests\Broadband;

use Illuminate\Foundation\Http\FormRequest;

class SatelliteRequest
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
            'power_type' => 'required|numeric',
            'building_type' => 'required|numeric',
            'roof_type' => 'required|numeric',
            'wall_type' => 'required|numeric',
            'visit_id' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'power_type.required'    => 'Power type is required.',
            'building_type.required' => 'Building type is required.',
            'roof_type.required'     => 'Roof type is required.',
            'wall_type.required'     => 'Wall type is required.',
            'visit_id.required'     => 'Wall type is required.',
        ];
    }
}
