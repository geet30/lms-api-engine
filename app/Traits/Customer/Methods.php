<?php

namespace App\Traits\Customer;

use App\Models\Lead;
use Illuminate\Support\Facades\{DB, Crypt};

/**
 * Customer Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    /**
     * Add data.
     * Author: Sandeep Bangarh
     */
    static function addOrUpdateData($request, $visitor)
    {
        $data = ['title' => null, 'first_name' => stripslashes(str_replace('.', '', $request->first_name)), 'middle_name' => null, 'last_name' => null];
        $data['email'] = $request->email;
        $data = self::setName($request, $data);

        $emailDomain = explode('@', $request->email);
        $data['domain'] = $emailDomain[1] ?? '';

        $data['alternate_phone'] = request('altphone', null);

        /** convert dd/mm/yyyy to mm/dd/yyyy **/
        $dob = request('dob', null);
        if ($dob) {
            $dob = explode('/', $dob);
            $dob = $dob[1] . '/' . $dob[0] . '/' . $dob[2];
            $data['dob'] = date('Y-m-d', strtotime($dob));
        }

        $data['phone'] = phoneNumber($request->phone);
        $data = self::applyGDPR($data);

        $resultObj = self::updateOrCreate(['id' => $visitor->visitor_id],  $data);
        $isUpdated = Lead::updateData(['lead_id' => $visitor->lead_id], ['visitor_id' => $resultObj->id, 'status' => 1]);
        return ['is_updated' => $isUpdated ];
    }

    /**
     * Add fields in GDPR.
     * Author: Sandeep Bangarh
     */
    static function applyGDPR ($data) {
        foreach(self::$gdprFields as $gdprField) {
            if (isset($data[$gdprField]) && $data[$gdprField]) {
                $data[$gdprField] = encryptGdprData($data[$gdprField]);
            }
        }
        return $data;
    }

    /**
     * remove GDPR.
     * Author: Sandeep Bangarh
     */
    static function removeGDPR ($data) {
        $isObject = false;
        if (is_object($data)) {
            $data = (array) $data; 
            $isObject = true;
        }
        foreach(self::$gdprFields as $gdprField) {
            if (isset($data[$gdprField]) && $data[$gdprField]) {
                $data[$gdprField] = decryptGdprData($data[$gdprField]);
            }
        }
        if ($isObject) {
            return (object) $data;
        }
        return $data;
    }

    /**
     * Set name.
     * Author: Sandeep Bangarh
     */
    static function setName($request, $data)
    {
        if ($request->filled('first_name')) {
            $name = explode(' ', trim($request->first_name)); //Name as an array.
            if (count($name)) {
                $data['first_name'] = stripslashes($name[0]);

                if (count($name) >= 3) {
                    $nameArr = $name;
                    unset($nameArr[0]);
                    unset($nameArr[count($name)-1]);
                    $data['middle_name'] = implode(' ', $nameArr);
                    $data['last_name'] = stripslashes(end($name));
                }
            }

            if (!$data['last_name'] && count($name) > 1) {
                $data['last_name'] = stripslashes(end($name));
            }
        }

        if ($request->filled('title')) {
            $data['title'] = request('title', $data['title']);
        }

        if ($request->filled('last_name')) {
            $data['last_name'] = request('last_name', $data['last_name']);
        }
        
        if ($request->filled('middle_name')) {
            $data['middle_name'] = request('middle_name', $data['middle_name']);
        }

        return $data;
    }

    /**
     * Update data and add logs.
     * Author: Sandeep Bangarh
     */
    static function updateData($conditions, $data, $visitor = null)
    {
        $isUpdated = self::where($conditions)->update($data);
        if ($isUpdated) {
            self::addLogs((object) ['id' => $visitor->visitor_id, 'phone' => $data['phone']]);
        }
        return $isUpdated;
    }

    /**
     * Get remarketing token.
     * Author: Sandeep Bangarh
     */
    static function getRemarketingToken($leadId)
    {
        $marketingData = DB::table('marketing')->where('lead_id', $leadId)->first();
        $parameters = ['rc', 'cui', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid'];
        $visitorRemarketingToken = $leadId . '|email';
        
        $visitorRemarketingToken = Crypt::encryptString($visitorRemarketingToken);
        foreach ($parameters as $parameter) {
            if (!empty($marketingData->{$parameter}))
                $visitorRemarketingToken .= '&' . $parameter . '=' . $marketingData->{$parameter};
        }
        return $visitorRemarketingToken;
    }

    static function addDuplicateCheck ($duplicateData) {

    }


    static function saveVisitor($request)
    {
        $data = ['title' => null, 'first_name' => stripslashes(str_replace('.', '', $request->first_name)), 'last_name' => null];
        $data['email'] = $request->email;
        $data = self::setName($request, $data);

        $data['alternate_phone'] = request('altphone', null);

        /** convert dd/mm/yyyy to mm/dd/yyyy **/
        $dob = request('dob', null);
        if ($dob) {
            $dob = explode('/', $dob);
            $dob = $dob[1] . '/' . $dob[0] . '/' . $dob[2];
            $data['dob'] = date('Y-m-d', strtotime($dob));
        }

        $data['phone'] = phoneNumber($request->phone);
        $data = self::applyGDPR($data);
        return self::create($data);
       
        
        
    }
}
