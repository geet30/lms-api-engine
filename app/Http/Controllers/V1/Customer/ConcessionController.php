<?php

namespace App\Http\Controllers\V1\Customer;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\{ConcessionContent,VisitorConcessionDetail};
use App\Http\Requests\Energy\ConcessionRequest;

class ConcessionController
{
   public function getConcessionContent(Request $request){
      try{
          
       return  ConcessionContent::getConcessionContent($request);
      } catch (\Exception $e) {
        return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PROVIDERSECTION_ERROR_CODE);
    }
   }
   public function saveConcessionDetails(Request $request){
      try{
         $reqObj = new ConcessionRequest($request);
         $validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
         if ($validator->fails()) {
            $status = false;
            $statusCode = HTTP_STATUS_VALIDATION_ERROR;
            $message = HTTP_ERROR;
            $data = $validator->errors();
            return response()->json([
                'status' => $status,
                'message' => $message,
                'response' => $data,
            ], $statusCode);
        }
       return  VisitorConcessionDetail::saveConcessionDetails($request);
      } catch (\Exception $e) {
        return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PROVIDERSECTION_ERROR_CODE);
    }
   }
}