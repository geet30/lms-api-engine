<?php
namespace App\Http\Requests\Broadband;

class PlanListRequest
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
		$request = $this->request;
        $rules = [
            'connection_type'=>'required',
            'technology_type'=>'required',
            'visit_id'=>'required',
            'address'=>'required',
            'movein_type'=>'required',
            'movein_date'=>'required_if:movein_type,1',
        ];
  
        return $rules;
    }

    public function messages()
    {
        $message = array();
		$message['connection_type.required']='Connection type is required';
		$message['technology_type.required']='Technology is required';
		$message['visit_id.required']='Lead id is required';
		$message['address.required']='Address is required';
		$message['movein_type.required']='Move-in type is required';
		$message['movein_date.required_if']='Move-in date is required';
		
	
		return $message;
    }
}
