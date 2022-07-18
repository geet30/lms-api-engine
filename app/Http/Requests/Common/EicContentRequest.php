<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class EicContentRequest
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
            // 'provider_id' => 'required',
            "state" => "required"
        ];
    }

    public function messages()
    {
        return [
            // 'provider_id.required' => 'Provider id is required.',
            // 'post_id.required' => 'Post id is required.',
            'state.required' => 'State is required.'
        ];
    }
}
