<?php

namespace App\Http\Controllers\V1\Plans\Energy;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Energy\{PlanListRequest, SaveAuthTokenRequest};
use App\Traits\LambdaTokenEx\LambdaTokenEx;
use App\Http\Requests\Energy\PlanDeatilRequest;
use App\Repositories\Energy\FilterOptions;
use App\Repositories\Energy\SetDmoContent;
use App\Models\{Lead, PlanEnergy, PlanRefs};

class DmoContentController extends Controller
{
   

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    function getDmoContent(Request $request){
        $validator = Validator::make($request->all(), ['visit_id' => 'required','elec_distributor_id'=>'required'],['visit_id.required' => 'visit_id field is required or you are passing wrong parameter for visit_id','elec_distributor_id'=>'elec_distributor_id field is required or you are passing wrong parameter for elec_distributor_id']);
		
		if ($validator->fails()) {
			$response = ['status' => 0, 'errors' => $validator->errors()];
            
			return $response;
           
		}
        $setContent = new SetDmoContent();
         $plans =  PlanEnergy::getDmoPlanData($request); 
         
        $dmoContent= $setContent->setContent($plans,$request);

        if(count($dmoContent)){

            $response = ['status' => 1, 'message' => 'Dmo Text found successfully.', 'data' => $dmoContent];
			$status = 200;
		} else {
			$response = ['status' => 0, 'message' => 'No found Dmo content', ];
			$status = 200;
		}
        
        return response()->json($response, $status);
        
    }

}
