<?php

namespace App\Repositories\Energy;


use App\Models\{
    Providers,
    EnergyPlanRate,EnergyContentAttribute,Distributor,DmoVdoPrice,
    PlanDmo as ModelsPlanDmo
};
use App\Models\Energy\EnergyLeadJourney;
use App\Models\Energy\EnergyBillDetails;

trait PlanDmo
{
    static function getDmoPlanData($requestData){
        
           $plansTariff = $requestData->electricity_plan_arr;
           $data['planDmoAttributes']=  EnergyContentAttribute::where('type',1)->pluck('attribute');
           $data['masterDmoAttributes']=  EnergyContentAttribute::where('type',2)->pluck('attribute');
           $data['masterDmoContent']=ModelsPlanDmo::where('plan_rate_id',null)->get();
          
           $select=['id','lead_id','energy_type','property_type','distributor_id'];
           $data['leadData'] = EnergyLeadJourney::getLeadDataCommon(decryptGdprData($requestData->visit_id),$select)->first();
           $selectBill=['lead_id','peak_usage','off_peak_usage','shoulder_usage','control_load_one_usage','control_load_two_usage'];
           $data['billData'] = EnergyBillDetails::getBillDataCommon(decryptGdprData($requestData->visit_id),$selectBill)->first();
           
           $data['distributorData']= Distributor::getDistributorCommon($requestData->elec_distributor_id,['id','name'])->first();
           $data['dmoVdoprices'] = DmoVdoPrice::where('distributor_id', $requestData['elec_distributor_id'])->where("offer_type", $requestData['dmo_vdo_type'])->where('property_type',$data['leadData']['property_type'])->get();
          
           $planIds = array_keys($plansTariff);
           $tariffTypes = array_values($plansTariff);
           $data['planData'] =  self::select('provider_id','id','name')->whereIn('id',$planIds)->where('status',1)->with([
                'rate'=>function($query)use($tariffTypes){
                    $query->whereIn('type',$tariffTypes)->with('planRateLimit','PlanDmoContent');
                    }
            ,'provider'])->get()->toArray();
            
            return $data;
    }
    /**
     * @param array $input
     *
     * @throws GeneralException // @phpstan-ignore-line
     *
     * @return bool
     */
}
