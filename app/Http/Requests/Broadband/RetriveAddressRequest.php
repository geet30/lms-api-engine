<?php
namespace App\Http\Requests\Broadband;

use Illuminate\Foundation\Http\FormRequest;

class RetriveAddressRequest
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
            'record_id'=> 'required|size:14'
        ];
    }

    public function messages()
    {
        return [
            'record_id.required'=>'Record Id is required.',
            'record_id.size'=>'Record Id must be 14 characters.',
        ];
    }
}
