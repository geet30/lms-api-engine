<?php
namespace App\Repositories\Addons;
use App\Models\{
    PlanAddonsMaster,
    PlanAddonMasterTechType,
}; 
use DB;
trait BasicCrud
{
    public static function addAddons($request , $category){
        if($category == 3){
        $data = $request->only('category','provider_id','name','cost','cost_type_id','order','inclusion','script','description');
        $data['service_id'] = 3;
        $data['status'] = 1;
        PlanAddonsMaster::create($data);
        }
        if($category == 4){
        $data = $request->only('category','name','connection_type','order','description');
        $data['service_id'] = 3;
        $data['status'] = 1;
        $addon = PlanAddonsMaster::create($data);
        if($addon && $request->has('tech_type')) {
            $insertTech =[];
            foreach ($request->tech_type as $value) {
                $insertTech[] = [
                            'addon_id'=>$addon->id,
                            'tech_type'=>$value, 
                           ];
            }
            PlanAddonMasterTechType::insert($insertTech); 
        }
        }
        if($category == 5){
        $data = $request->only('category','name','order','description');
        $data['service_id'] = 3;
        $data['status'] = 1;
        PlanAddonsMaster::create($data);
        }
    }
    public static function updateAddons($request , $id){
        if($id == 3){
            $addon_id = $request->addon_id;
        $data = $request->only('provider_id','name','cost','cost_type_id','order','inclusion','script','description');
        PlanAddonsMaster::find($addon_id)->update($data);
        }
        if($id == 4){
            $addon_id = $request->addon_id;
        $data = $request->only('name','connection_type','order','description');
        $addon = PlanAddonsMaster::find($addon_id)->update($data);
        if($request->has('tech_type')) {
            $selectedTech =  $request->tech_type;
            $assignTech = PlanAddonMasterTechType::where('addon_id',$addon_id)->pluck('tech_type')->toArray(); 

            $deleteTechs=array_diff($assignTech,$selectedTech); 
            $insertTechs = array_diff($selectedTech,$assignTech);

            PlanAddonMasterTechType::where('addon_id',$addon_id)->whereIn('tech_type',$deleteTechs)->delete();
            $insertTech =[];
            foreach ($insertTechs as $value) {
                $insertTech[] = [
                            'addon_id' => $addon_id,
                            'tech_type' => $value, 
                           ];
            }
            PlanAddonMasterTechType::insert($insertTech); 
            
        }
        }
        if($id == 5){
            $addon_id = $request->addon_id;
        $data = $request->only('name','order','description');
        $data['service_id'] = 3;
        PlanAddonsMaster::find($addon_id)->update($data);
        }
    }
    public static function updateStatus($request)
    {   
        try
        {
            $addonId= $request->addon_id;
            return PlanAddonsMaster::where('id', $addonId)->update(['status' => $request['status']]);
        }
        catch (\Exception $err) { 
            throw $err;
        }
    }
    public static function deleteAddons($id)
    {   
       $addon = PlanAddonsMaster::find($id);
       if($addon->category == 4){
           $techtype = PlanAddonMasterTechType::where('addon_id','=',$id);
           $techtype->delete();
       }
       $addon->delete();
    }
    
}