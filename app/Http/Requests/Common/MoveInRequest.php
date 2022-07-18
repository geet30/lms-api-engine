<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class MoveInRequest
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
            'post_code' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'post_code.required' => 'Post Code id required.'
        ];
    }
}
