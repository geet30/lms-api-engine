<?php
namespace App\Repositories\Plan\Broadband;
use App\Models\{
    Provider,
    PlansBroadband,
    PlansTelcoContent,
    ConnectionType,
    Contract,
    CostType,
    Fee,
    PhoneHomeLineConnection,
    BroadbandAdditionalAddons,
    BroadbandModem,
    PlansBroadbandAddon,
    PlansBroadbandEicContent,
    PlansBroadbandContentCheckbox
};
trait Master
{ 
    /**
     * get list of technology by selected connection type.
     *
     * @param  int  $connId
     * @return \Illuminate\Http\Response
     */
    public static function getTechType($connId)
    {
        return ConnectionType::where('connection_type_id',$connId)->where('service_id',3)->where('is_deleted','0')->where('status','1')->whereNotNull('name')->whereNotNull('connection_type_id')->select('id','name')->get(); 
    }
    
    /**
     * get list of all connection type.
     * 
     * @return \Illuminate\Http\Response
     */
    public static function getConnectionType()
    {
        return ConnectionType::select('id','name')->where('service_id', 3)->where('is_deleted','0')->where('status','1')->whereNull('connection_type_id')->get(); 
    }
    
    /**
     * get list of all contract.
     *
     * @param  int  $planId
     * @return \Illuminate\Http\Response
     */
    public static function getContracts()
    {
        return Contract::select('id','validity','contract_name','contract_unique_id')->where('status', 1)->orderBy("validity",'ASC')->get(); 
    }

    /**
     * get list of all fees
     *
     * @param  int  $planId
     * @return \Illuminate\Http\Response
     */
    public static function getCostTypes()
    {
        return CostType::select('id','cost_name')->where('status','1')->where('is_deleted','0')->orderBy('order','asc')->get();
    }

    /**
     * get list of all cost type
     *
     * @param  int  $planId
     * @return \Illuminate\Http\Response
     */
    public static function getFeeTypes()
    {
        return Fee::select('id','fee_name')->where('status','1')->where('is_deleted','0')->orderBy('order','asc')->get();
    }

}
