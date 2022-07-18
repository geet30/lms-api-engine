<?php

namespace App\Traits\EnergyLeadJourney;

use Illuminate\Support\Facades\DB;

/**
 * EnergyLeadJourney methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
  /**
   * Update Percentage.
   * Author: Sandeep Bangarh
   */
  static function updatePercentage($leadId, $request)
  {
    $dataToUpdate = [];
    $dataToUpdate['lead_id'] = $leadId;
    $dataToUpdate['percentage'] = $request->percentage;
    $dataToUpdate['step_name'] = $request->step_name;
    if ($request->has('screen_no') && $request->screen_no) {
      $dataToUpdate['screen_name'] = self::getScreenName($request->screen_no);
    }

    if ($request->has('screen_name') && $request->screen_name) {
      $dataToUpdate['screen_name'] = $request->screen_name;
    }

    if ($request->has('energy_type') && $request->energy_type == 'electricity') {
      $dataToUpdate['energy_type'] = 1;
    }

    if ($request->has('energy_type') && $request->energy_type == 'gas') {
      $dataToUpdate['energy_type'] = 2;
    }

    $energyData = DB::table('lead_journey_data_energy')->where('lead_id', $leadId)->exists();
    
    if ($request->has('energy_type') && $request->energy_type == 'electricitygas' && !$energyData) {
      $cloneArray = [];
      $cloneArray[] = $dataToUpdate;
      $cloneArray[] = $dataToUpdate;
      $cloneArray[0]['energy_type'] = 1;
      $cloneArray[1]['energy_type'] = 2;
      return self::insert($cloneArray);
    }
    
    if (!$energyData) return self::create(array_merge($dataToUpdate, ['lead_id' => $leadId]));
    return self::where('lead_id', $leadId)->where('percentage', '<', $request->percentage)->update($dataToUpdate);
  }

  /**
   * Get screen name w.r.t screen number.
   * Author: Sandeep Bangarh
   */
  static function getScreenName($screenNo)
  {
    $screenName = '';
    switch ($screenNo) {
      case 1:
        $screenName = "Energy Type";
        break;

      case 2:
        $screenName = "Property Type";
        break;

      case 3:
        $screenName = "Solar option";
        break;

      case 4:
        $screenName = "Move-In/Life Support";
        break;

      case 5:
        $screenName = "Electricity Usage";
        break;

      case 6:
        $screenName = "Gas Usage";
        break;

      case 7:
        $screenName = "Distributor Screen";
        break;

      case 8:
        $screenName = "Sign-Up Screen";
        break;

      case 9:
        $screenName = "Plan listing";
        break;
      case 9:
        $screenName = "Plan listing ";
        break;

      case 10:
        $screenName = "Plan Detail ";
        break;

      case 11:
        $screenName = "Account Details ";
        break;

      case 13:
        $screenName = "Address Screen ";
        break;

      case 14:
        $screenName = "Concession Screen ";
        break;

      case 15:
        $screenName = "Identification Screen ";
        break;

      case 16:
        $screenName = "Confirmation Screen ";
        break;

      case 17:
        $screenName = "Document Upload Screen ";
        break;

      default:
        # code...
        break;
    }
    return $screenName;
  }

  static function setIgnoreParameters($request)
  {
    $ignoredParameter = [];
    $mandatoryData = array('percentage', 'step_name', 'screen_no', 'screen_name', 'visit_id');
    $requestData = $request->all();
    foreach (array_keys($requestData) as $value) {
      if (!in_array($value, $mandatoryData)) {
        $ignoredParameter[] = $value;
      }
    }
    $ignoreParams = implode(',', $ignoredParameter);
    return $ignoreParams;
  }

  static function getLeadDataCommon($leadId,$select){
     return  self::where('lead_id',$leadId)->select($select)->get();

  }

 
}
