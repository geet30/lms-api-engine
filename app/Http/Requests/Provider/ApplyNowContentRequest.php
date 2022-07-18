<?php
namespace App\Http\Requests\Provider;

class ApplyNowContentRequest 
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
        /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(){
        $serviceId = $this->request->header('ServiceId');
        // echo "<pre>";print_r($serviceId);die;
        $rules = array();
        if($serviceId !='1'){
            $rules = [
                'provider_id'=>'required'
            ];
        }
        else{
            // $rules = array(
            //     'provider_id' => 'required_without:gas_provider_id'
            // );
        }
		
        return $rules;
    }
        
    public function messages(){
        $serviceId = $this->request->header('ServiceId');
        $message = array();
        if($serviceId !='1'){
            $message['provider_id.required']='provider id is required';
        }
        else{
           $message['provider_id.required_without']='provider id is required';
        }
	    return $message;
    }
}
