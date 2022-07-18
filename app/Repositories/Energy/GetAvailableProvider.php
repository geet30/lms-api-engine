<?php

namespace App\Repositories\Energy;


use App\Models\{Providers,AssignedUsers,ProviderMoveIn,UserStates,States,ProviderPermission
};
use DB;
use Storage;
use Illuminate\Validation\Rule;
use Session;
use Carbon\Carbon;
use Auth;
trait GetAvailableProvider
{

    static function availableDistributor($distributorId){
        $affiliatedistributor = AssignedUsers::where('source_user_id',Auth::user()->id)->where('service_id',1)->where('relation_type',5)->where('relational_user_id',$distributorId)->select('relational_user_id')->first();
        if($affiliatedistributor){
            return $affiliatedistributor->relational_user_id;
        }else{
            return null;
        }
    }

    static function availableProviders($requestData){

        
        $affiliateProviders = AssignedUsers::where('source_user_id',Auth::user()->id)->where('service_id',1)->with(['providers'=>function($q){
            $q->with(['logo','content'=>function($query){
                $query->where('type','12')->select('status','provider_id');
            }]);
        }])->where('service_id',1)->where('relation_type',1)->where('status',1)->get();
      
        return $affiliateProviders;

    }
    static function getMoveInProviders($distributor, $requestData, $moveInDays,$allProviders,$energyType)
    {
        $moveInProviders = [];
        $unsetIds = [];
            $moveInProviders = ProviderMoveIn::where('distributor_id',$distributor)->where('grace_day', '!=', 0)
            ->where('grace_day', '<=', $moveInDays)->where('property_type', $requestData['property_type'])->where('energy_type',$energyType)->whereIn('user_id',$allProviders)->select('user_id','grace_day','restricted_start_time')->get();
           
            date_default_timezone_set("Australia/Sydney");
            $mytime = Carbon::now()->format('H:i:s');
            $moveInProviderIds = $moveInProviders->pluck('user_id')->toArray();
           
            foreach($moveInProviders as $key=>$mPorvider){
                if($mPorvider->grace_day == $moveInDays){
                   if(strtotime($mPorvider->restricted_start_time) < (strtotime($mytime))){
                     unset($moveInProviderIds[$key]);
                   }
                }
            }
            if(count($moveInProviderIds)){
                return $moveInProviderIds;
            }
            return [];
    }
    static function  checkAssginedPostcode($allProviders,$request,$postcode){
        $stateId = States::select('state_id')->where('state_code',trim($postcode[2]))->first();
        $matchSubrub=[];
       $users=  UserStates::where('state_id',$stateId->state_id)->where('status',1)->whereIn('user_id',$allProviders)->with('userSubrubs.userPostcode.postcode')->get()->toArray();
       
       
        foreach($users as $user){
            
            foreach($user['user_subrubs'] as $suburb){
              
                if(isset($suburb['user_postcode']['postcode']) && $suburb['user_postcode']['postcode']['postcode'] == trim($postcode[0])){
                    $matchSubrub[]=$user['user_id'];
                }
            }
        }
           
            return $matchSubrub;
    }

    static function  checkproviderPermissions($allProviders,$lifeSuport,$lifeSuportEnergy,$currentProvider,$request,$gasOnly){
       
        $checkProviderSettings= ProviderPermission::whereIn('user_id',$allProviders);
        
        if($lifeSuport == 1){
           
            $checkProviderSettings = $checkProviderSettings->where('is_life_support',1);
            
            if($lifeSuportEnergy == 1){
                
                $type = [1,3];
                $checkProviderSettings= $checkProviderSettings->whereIn('life_support_energy_type',$type);
            }elseif($lifeSuportEnergy == 2){
                
                $type = [2,3];
                $checkProviderSettings= $checkProviderSettings->whereIn('life_support_energy_type',$type);
            }
           
        }
        if($gasOnly){
            $checkProviderSettings= $checkProviderSettings->where('is_gas_only',1);
        }
        if($request['credit_score'] != ''){
            $checkProviderSettings= $checkProviderSettings->where('ea_credit_score_allow',1)->where('credit_score','>=',$request['credit_score']);
        }
        
        if ( isset($request['is_agent']) && $request['is_agent'] == 1) {
            
            $checkProviderSettings= $checkProviderSettings->where(function ($q) {
                $q->Where(['is_telecom' => 1, 'is_send_plan' => 1])
                ->orWhere(['is_telecom' => 0, 'is_send_plan' => 1])
                ->orWhere(['is_telecom' => 1, 'is_send_plan' => 0]);
            });
        }
        $checkProviderSettings= $checkProviderSettings->get();
        return $checkProviderSettings;
        
    }

    /**
     * @param array $input
     *
     * @throws GeneralException // @phpstan-ignore-line
     *
     * @return bool
     */


}
