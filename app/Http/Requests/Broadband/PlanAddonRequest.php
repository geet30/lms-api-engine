<?php
namespace App\Http\Requests\Broadband;

use Illuminate\Foundation\Http\FormRequest;

class PlanAddonRequest
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
            'sale_product_id' => 'required'
            //'selected_addons' => ['required_if:sale_product_id,1','array']
        ];
    }

    public function messages()
    {
        return [
            'sale_product_id.required' => 'Sale product id is required.'
            //'selected_addons.required_if' => 'Invalid array.'
        ];
    }
}
