<?php
namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;

class SendPlanRequest
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
        $rules['plan_id'] = 'required';
        $rules['visit_id'] = 'required';
        if($this->request->header('serviceId') == 1){//this for only broadband, mobile
            $rules['plan_url'] = 'required';
           // $rules['gas_plan_url'] = 'required_if:gas_plan_id';
            $rules['elec_tariff_type'] = 'required';
            $rules['template_type'] = 'required';
           
        } elseif($this->request->header('serviceId') == 2) {
            $rules['plan_type'] = 'required';
            $rules['template_type'] = 'required';
            if($this->request->plan_type == 2){
                $rules['handset_id'] = 'required';
                $rules['variant_id'] = 'required';
                
            }
            
            
        }elseif($this->request->header('serviceId') == 3) {
           
            
        }
        
        return $rules;
    }

    public function messages()
    {
        $message = array();
        
        return $message;
    }
}
