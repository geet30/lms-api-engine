<?php
namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class PlanApplyRequest
{

    protected $request;

    function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = array();
        $rules['visit_id'] = 'required';
        if($this->request->header('serviceId') != 1){//this for only broadband, mobile
            $rules['plan_id'] = 'required';
            $rules['cost_type'] = 'required';
            $rules['cost'] = 'required|numeric|gte:0';
        } else {
            if($this->request->has('plan_id') && ($this->request->input('plan_id') == null || $this->request->input('plan_id') == '')){
                $rules['plan_id'] = 'required';
                $rules['cost_type'] = 'required';
                $rules['cost'] = 'required|numeric|gte:0';
            }
            if($this->request->has('gas_plan_id') && ($this->request->input('gas_plan_id') == null || $this->request->input('gas_plan_id') == '')){
                $rules['gas_plan_id'] = 'required';
                $rules['gas_cost_type'] = 'required';
                $rules['gas_cost'] = 'required|numeric|gte:0';
            }
        }
        
        return $rules;
    }

    public function messages()
    {
        $message = array();
        $message = [
            'visit_id.required' => 'Visit id is required',
            'plan_id.required' => 'Plan id is required',
            'cost_type.required' => 'Cost type is required',
            'cost.required' => 'Please enter cost.',
            'cost.numeric' => 'Please enter valid cost.',
            'cost.gte' => 'Please enter valid cost.',
            'gas_plan_id.required' => 'Gas plan id is required',
            'gas_cost_type.required' => 'Gas cost type is required',
            'gas_cost.required' => 'Please enter cost.',
            'gas_cost.gte' => 'Please enter valid cost..'        
        ];
        return $message;
    }
}
