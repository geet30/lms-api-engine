<?php

namespace App\Traits\Identification;

/**
 * Identification Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    private static $sections = ['license' => 'Drivers Licence','passport'=>'Passport','foreignPassport'=>'Foreign Passport','medicare'=> 'Medicare Card'];

    private static $licenseFields = ['identification_type','licence_state_code', 'licence_number', 'licence_expiry_date'];

    private static $passportFields = ['identification_type','passport_number','passport_expiry_date'];

    private static $foreignPassportFields = ['identification_type','foreign_passport_number','foreign_passport_expiry_date','foreign_country_name','foreign_country_code'];

    private static $medicareFields = ['identification_type','medicare_number','reference_number','card_color','medicare_card_expiry_date','card_middle_name'];

    private static $commonFields = ['identification_option','identification_content'];

    /**
     * Add identification.
     * Author: Sandeep Bangarh
     */
    static function addIdentification($request) {
        $dataToSave = static::setIdentificationFields($request);
        return self::insert($dataToSave);
    }

    /**
     * Set identification fields.
     * Author: Sandeep Bangarh
     */
    static function setIdentificationFields ($request) {
        $dataToSave = [];
        $identify = new \App\Models\VisitorIdentification;
        $leadId = decryptGdprData($request->visit_id);
        foreach($request->identification_details as $key => $idntifyObj) {
            foreach(static::$sections as $title) {
                if ($idntifyObj['identification_type'] == $title) {
                    foreach($identify->fillable as $field) {
                        $dataToSave[$key][$field] = '';
                        if (isset($idntifyObj[$field]) && $idntifyObj[$field]) {
                            $dataToSave[$key][$field] = $idntifyObj[$field]; 
                        }
                        $dataToSave[$key]['lead_id'] = $leadId;
                        $dataToSave[$key]['identification_content'] = $request->identification_content;
                    }
                }
            }
        }
        return $dataToSave;
    }

    static  function saveIdentification($requestData){
         return self::create($requestData->all());
    }

    /**
     * Check identification existance regard lead.
     * Author: Sandeep Bangarh
     */
    static function identificationExist ($request) {
        $leadId = decryptGdprData($request->visit_id);
        return self::where('lead_id', $leadId)->exists();
    }

    /**
     * Delete identification.
     * Author: Sandeep Bangarh
     */
    static function clearIdentification ($leadId) {
        $leadId = decryptGdprData($leadId);
        self::where('lead_id', $leadId)->delete();
    }
}