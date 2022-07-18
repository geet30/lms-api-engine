<?php
namespace App\Http\Requests\Broadband;

use Illuminate\Foundation\Http\FormRequest;

class PlanAddonListRequest
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
            'plan_id' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'plan_id.required' => 'Plan id is required.'
        ];
    }
}
