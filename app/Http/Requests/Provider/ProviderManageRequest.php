<?php
namespace App\Http\Requests\Provider;


class ProviderManageRequest 
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
		$rules = array();
        $rules = [
            'provider_id'=>'required',
        ];
  
        return $rules;
    }
        
    public function messages(){
		$message = array();
        $message['provider_id.required']='provider id is required';
		return $message;
    }
}
