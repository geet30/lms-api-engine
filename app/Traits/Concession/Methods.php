<?php

namespace App\Traits\Concession;

use Illuminate\Support\Facades\DB;
use App\Models\{Lead,ConcessionContent,VisitorConcessionDetail};
use Illuminate\Support\Facades\Auth;
/**
* Connection methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{
    static function getConcessionContent($request){
        try{
            $leadId = decryptGdprData($request->visit_id);
            $columns = ['visitor_addresses.state','visitor_addresses.property_name'];
            $states = Lead::getFirstLead(
                ['leads.lead_id' => $leadId],
                $columns,
                null,
                null,
                null,
                null,
                null,
                true
            );
            $property_name = isset($states->property_name)?$states->property_name:'';
            $data = null;
            $response =[];
            if($states){
                $attributes = DB::table('energy_content_attributes')->where(['type' => 3,'service_id'=>1])->get()->pluck('attribute')->toArray();
                $company = DB::table('affiliates')->select('company_name')->where('user_id',Auth::user()->id)->first();
                $provider_id = DB::table('sale_products_broadband')->select('providers.name')->join('providers','providers.id','sale_products_broadband.provider_id')->first();
                $state = DB::table('states')->select('state_id')->where('state_code','NSW')->first();
                $data = DB::table('concession_content')->select('id','content')->where([
                    'state_id'    => $state->state_id,
                    'provider_id' => $request->provider_id
                  ])->first();
                $concession_data = [];
                $concession_data['provider_name'] =  isset($provider_id->name)?$provider_id->name:'';
                $concession_data['affiliate_name'] = isset($company->company_name)?$company->company_name:'';
                $concession_data['property_name'] =  isset($property_name)?$property_name:''; 
                
                if($data){
                    $response['content'] = str_replace($attributes, $concession_data, $data->content);
                    $response['id'] =  $data->id;
                }
                 
            }
            return successResponse(!empty($response) ? 'Data fetched successfully' : 'Data not found',200,$response);


        } catch (\Exception $e) {
          return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PROVIDERSECTION_ERROR_CODE);
      }
    }
    static function saveConcessionDetails($request){
        try{
            $concession_id = Lead::where('lead_id',decryptGdprData($request->visit_id))->first()->visitor_concession_details_id;
            $data['concession_type'] = $request->concession_type;
            $data['card_number'] = $request->card_number; 
            $data['card_start_date'] = $request->card_issue_date;
            $data['card_expiry_date'] = $request->card_expiry_date;
            $response = VisitorConcessionDetail::updateOrCreate (['id' => $concession_id],$data);
            if($response){
                if(!$concession_id){
                    Lead::where('lead_id',decryptGdprData($request->visit_id))->update([
                       'visitor_concession_details_id' => $response->id
                    ]);
                }
                return successResponse("Concession details have been successfully updated.",200);
            }
            return successResponse("Something went wrong while saving data.",402);
        }
        catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, PROVIDERSECTION_ERROR_CODE);
        }
    }
}