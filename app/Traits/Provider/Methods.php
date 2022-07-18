<?php

namespace App\Traits\Provider;

use Illuminate\Support\Facades\DB;
use App\Models\{AssignedUsers, ProviderContent,ProviderPermission};

/**
 * Provider Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    static function getProviderTermsConditions($affilite_id)
    {
        $termData = self::whereHas('assigned_users', function ($query) use ($affilite_id) {
            $query->where('source_user_id', $affilite_id);
        })
        ->with('provider_term_conditions', function ($query) {
            $query->select('type', 'provider_id', 'title', 'description')->orderBy('type', 'asc');
        })
        ->where('is_deleted', '=', 0)->where('status', 1)->select('user_id', 'name')->get();

        $returnObj = [];
        foreach ($termData as $key => $provider) {
            if (!$provider->provider_term_conditions->isEmpty()) {
                $returnObj[$key]['provider_id'] = $provider->user_id;
                $returnObj[$key]['provider_name'] = $provider->name;
                foreach ($provider->provider_term_conditions as $k => $term) {
                    $returnObj[$key]['terms_conditions'][$k]['s_no'] = $term->type;
                    $returnObj[$key]['terms_conditions'][$k]['title'] = $term->title;
                    $returnObj[$key]['terms_conditions'][$k]['description'] = $term->description;
                }
            }
        }
        return $returnObj;
    }

    static function getPermission($request){
      $data = ProviderPermission::select('id','is_new_connection','is_port','is_retention','connection_script','port_script','recontract_script')->with(['checkbox' => function($query){
          $query->select('provider_content_id','checkbox_required','content')->where('type',ProviderPermission::TYPE);
      }])->where('user_id',$request->provider_id)->get();
      if($data->isEmpty()){
        return successResponse('Data is not found',200,$data);    
      }
      return successResponse('Data found successfully',200,$data);
      
    }
}
