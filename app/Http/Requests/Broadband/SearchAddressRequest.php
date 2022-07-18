<?php
namespace App\Http\Requests\Broadband;

use Illuminate\Foundation\Http\FormRequest;

class SearchAddressRequest
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
            'search_address' => 'required|min:10'
        ];
    }

    public function messages()
    {
        return [
            'search_address.required' => 'Please enter your complete address.',
            'search_address.min' => 'Address must be minimum 10 characters.'
        ];
    }
}
