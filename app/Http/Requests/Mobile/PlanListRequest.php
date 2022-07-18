<?php
namespace App\Http\Requests\Mobile;


class PlanListRequest 
{

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

      if($this->request->plan_type==2){
          $rules = ['visit_id' => 'required',
          'plan_type'=>'required',
          'connection_type'=>'required',
          'plan_cost_max'=>'required',
          'plan_cost_min'=>'required',
          'data_usage_min'=>'required',  
         
          'handset_filter'=>'required',
          'variant_filter'=>'required',         
        ];
      }else{
          $rules = ['visit_id' => 'required',
          'plan_type'=>'required',
          'connection_type'=>'required',  
         
          'plan_cost_max'=>'required',
          'plan_cost_min'=>'required',        
        ];
      }
     
      return $rules;
    }
        
    public function messages(){
      $message = array();
      $message['visit_id.required']='Visit Id is required';   
      return $message;
    }
}
