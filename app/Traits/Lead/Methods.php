<?php

namespace App\Traits\Lead;

use App\Models\{AffiliateKeys, Affiliate, Marketing, SystemInfo, SaleProductsEnergy, ReconSale, SaleProductsBroadband, SaleProductsMobile, LeadJourneyDataMobileHandset, Service, VisitorAddress};

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Lead Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{

    /**
     * Create Visit.
     * Author: Sandeep Bangarh
     */
    static function createVisit($request)
    {
        $serviceId = $request->header('ServiceId');
        $user = auth()->user();
        $data = $request->all();
        $frameUpdateData = [];

        $frameUpdateData['affiliate_portal_type'] = '1';

        if ($request->has('connection_address_id') && $request->connection_address_id) {
            $frameUpdateData['connection_address_id'] = $data['connection_address_id'];
        }


        $frameUpdateData['affiliate_id'] = $user->id;
        $frameUpdateData['sale_source_id'] = $user->id;

        $frameUpdateData['api_key_id'] = AffiliateKeys::getId($request->header('API-Key'));
        $frameUpdateData['status'] = 0;
        $frameUpdateData['visitor_source'] = 1;

        /** Check if any referal code is sent with request or not **/
        if (!empty($data[config('codes.referal_code_keyword')])) {
            $refDeatils = self::referalDetails($request);
            
            if ($refDeatils) {
                $frameUpdateData['sub_affiliate_id'] = $refDeatils['sub_affiliate_id'];
                if ($refDeatils['sub_affiliate_id'] != '' && $refDeatils['sub_affiliate_id'] != 0) {
                    $frameUpdateData['sale_source_id'] = $refDeatils['sub_affiliate_id'];
                }
                $frameUpdateData['referal_code'] = $refDeatils['referal_code'];
                $frameUpdateData['referal_title'] = $refDeatils['referal_title'];
            }
        }
 
        $address = self::addAddress($request);
        if ($address) {
            $frameUpdateData['connection_address_id'] = $address->id;
        }

        $lead = self::addLeadAsVisit($frameUpdateData);

        self::addSystemInfo($request, $lead->lead_id);

        self::setUTMParameters($request, $lead->lead_id);

        return $lead->lead_id;
    }

    /**
     * Fetching Referal details with API Key or RC Code.
     * Author: Sandeep Bangarh
     */
    static function referalDetails($request)
    {
        /** Set key and fecth cached data **/
        $apiKey = $request->header('API-Key');
        $refralCode = $request->rc;
        $cacheKey = $apiKey . '_' . $refralCode;
        $referelDetails = self::getDataFromCache($cacheKey);
        if ($referelDetails) {
            return $referelDetails;
        }

        $finalData = [];
        $finalData['sub_affiliate_id'] = null;
        $finalData['referal_code'] = null;
        $finalData['referal_title'] = null;

        if ($refralCode) {
            $frameData = AffiliateKeys::frameReferalCode($refralCode);

            if ($frameData && self::checkKeysAffiliates($apiKey, $frameData->affiliate)) {
                $finalData['sub_affiliate_id'] = $frameData->user_id;
                $finalData['referal_code'] = $frameData->rc_code;
                $finalData['referal_title'] = $frameData->name;
                /** Add data into cache **/
                self::addDataIntoCache($cacheKey, $finalData);
                return $finalData;
            }
            
            $frameData = Affiliate::affiliateReferalCode($refralCode, ['user_id', 'referal_code', 'parent_id']);
            if ($frameData && self::checkKeysAffiliates($apiKey, $frameData)) {
                $finalData['sub_affiliate_id'] = $frameData->user_id;
                $finalData['referal_code'] = $frameData->referal_code;
                $finalData['referal_title'] = '';
                /** Add data into cache **/
                self::addDataIntoCache($cacheKey, $finalData);
                return $finalData;
            }

            return false;
        }

        $frameData = AffiliateKeys::getDataWithUser($apiKey, ['rc_code', 'role', 'affiliate_keys.user_id', 'affiliate_keys.name']);
        if (!$frameData->rc_code && $frameData->role == 10) {
            $finalData['sub_affiliate_id'] = $frameData->affiliate_id;
            $finalData['referal_code'] = $frameData->rc_code;
            $finalData['referal_title'] = decryptGdprData($frameData->name);
        }

        /** Add data into cache **/
        self::addDataIntoCache($cacheKey, $finalData);

        return $finalData;
    }

    /**
     * Validating API Key and RC Code.
     * Author: Sandeep Bangarh
     */
    static function checkKeysAffiliates($apiKey, $affiliate)
    {
        $subKeys = $affiliate->keys;
        $masterKeys = $affiliate->parent->keys;
        $apiKeyArr = [];
        if (!$subKeys->isEmpty()) {
            $apiKeyArr = array_merge($apiKeyArr, $subKeys->pluck('api_key')->toArray());
        }
        if (!$masterKeys->isEmpty()) {
            $apiKeyArr = array_merge($apiKeyArr, $masterKeys->pluck('api_key')->toArray());
        }
        $checkAPI = [];
        foreach ($apiKeyArr as $apiK) {
            $checkAPI[] = strtoupper(decryptGdprData($apiK));
        }

        if (!in_array($apiKey, $checkAPI)) {
            return false;
        }

        return true;
    }

    /**
     * Set UTM Parameters.
     * Author: Sandeep Bangarh
     */
    static function setUTMParameters($request, $leadId)
    {
        $frameUpdateData = [];
        $parameters = ['rc', 'cui', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_rm', 'utm_rm_source', 'utm_rm_date', 'utm_content', 'gclid', 'fbclid', 'msclkid'];
        foreach ($parameters as $key) {
            if ($request->has($key) && $request->{$key}) {
                $frameUpdateData[$key] = $request->{$key};
            }
        }
        if ($request->has(config('codes.customer_id_keyword')) && (int) $request->{config('codes.customer_id_keyword')}) {
            $frameUpdateData['customer_user_id'] = (int) $request->{config('codes.customer_id_keyword')};
        }
        if (!empty($frameUpdateData)) {
            return Marketing::addParamaeters($leadId, $frameUpdateData);
        }
        return false;
    }

    /**
     * Adding visitor data into database.
     * Author: Sandeep Bangarh
     */
    static function addSystemInfo($request, $leadId)
    {
        $frameUpdateData = [];
        if (isset($_SERVER['HTTP_USER_AGENT']) || $_SERVER['HTTP_USER_AGENT'] != '') {
            $agent = getUserAgent($_SERVER['HTTP_USER_AGENT']);
            $frameUpdateData['browser'] = $agent['browser'];
            $frameUpdateData['platform'] = $agent['platform'];
            $frameUpdateData['device'] = $agent['device'];
            $frameUpdateData['user_agent'] = $agent['user_agent'];
        }
        if ($request->has('screenResolution')) {
            $frameUpdateData['screen_resolution'] = $request->screenResolution;
        }

        $frameUpdateData['ip_address'] = $request->ip_address;

        if ($request->has('latitude') && $request->latitude != '' && $request->has('longitude') && $request->longitude != '') {
            /** if user has sent lat & long in request then same that **/
            $frameUpdateData['latitude'] = $request->latitude;
            $frameUpdateData['longitude'] = $request->longitude;
        }

        return SystemInfo::updateOrCreate(['lead_id' => $leadId], $frameUpdateData);
    }

    /**
     * Add Lead as Visit.
     * Author: Sandeep Bangarh
     */
    static public function addLeadAsVisit($frameUpdateData)
    {
        return self::create($frameUpdateData);
    }

    static function addReferenceNo($leadId, $products)
    {
        $refNos = [];
        $refNos[1] = static::setReferenceForEnergy($leadId, $products);
        $refNos[2] = static::setReferenceForMobile($leadId, $products);
        $refNos[3] = static::setReferenceForBroadband($leadId, $products);

        return $refNos;
    }

    static function setReferenceForEnergy($leadId, $products)
    {
        $energyData = $products->where('service_id', 1)->first();
        if ($energyData) {
            $elecData = $products->where('product_type', 1)->first();
            $gasData = $products->where('product_type', 2)->first();
            $elecProvider = $elecData ? $elecData['provider_id'] : '';
            $gasProvider = $gasData ? $gasData['provider_id'] : '';
            $time = Carbon::now()->timestamp;
            $firstRefNo = $elecData ? $time + $elecData['product_id'] + $elecProvider : null;
            $secondRefNo = $gasData ? $time + $elecData['product_id'] + $gasProvider : null;
            if ($elecData && $gasData && $elecProvider != $gasProvider) {
                $firstRefNo = $time + $elecData['product_id'] + $elecProvider;
                $secondRefNo = $time + $gasData['product_id'] + $gasProvider;
                SaleProductsEnergy::updateData(['lead_id' => $leadId, 'product_type' => 1], ['reference_no' => $firstRefNo]);
                SaleProductsEnergy::updateData(['lead_id' => $leadId, 'product_type' => 2], ['reference_no' => $secondRefNo]);
                return [$firstRefNo, $secondRefNo];
            }

            SaleProductsEnergy::updateData(['lead_id' => $leadId], ['reference_no' => $firstRefNo]);

            return [$firstRefNo, $secondRefNo];
        }

        return null;
    }

    static function setReferenceForMobile($leadId, $products)
    {
        $mobileData = $products->where('service_id', 2)->first();
        if ($mobileData) {
            $time = Carbon::now()->timestamp;
            $refNo = $time + $mobileData['product_id'] + $mobileData['provider_id'];
            SaleProductsMobile::updateData(['lead_id' => $leadId], ['reference_no' => $refNo]);
            return $refNo;
        }
        return null;
    }

    static function setReferenceForBroadband($leadId, $products)
    {
        $broadbandData = $products->where('service_id', 3)->first();
        if ($broadbandData) {
            $time = Carbon::now()->timestamp;
            $refNo = $time + $broadbandData['product_id'] + $broadbandData['provider_id'];
            SaleProductsBroadband::updateData(['lead_id' => $leadId], ['reference_no' => $refNo]);
            return $refNo;
        }
        return null;
    }

    static function addReconSale($leadId, $products, $visitor, $referenceNos)
    {
        $energyData = $products->where('service_id', 1);
        if (!$energyData) return true;

        $reconTableData = $energyRef = [];

        if (count($referenceNos)) {
            $energyRef = $referenceNos[1];
        }
        
        $elecRef = $gasRef = $commonRef = null;
        if (!empty($energyRef)) {
            $elecRef = $energyRef[0];
            $gasRef = $energyRef[1];
            $elecData = $energyData->where('product_type', 1)->first();
            $gasData = $energyData->where('product_type', 2)->first();

            if ($elecData && $gasData && $elecData['provider_id'] == $gasData['provider_id']) {
                $filteredArr = array_filter($energyRef);
                $commonRef = end($filteredArr);
            }
        }
        
        foreach ($energyData as $product) {
            $reconData = [];
            $reconData['lead_id']              = $leadId;
            $reconData['sale_reference_no']    = $commonRef??($product['product_type'] == 1 ? $elecRef : $gasRef);
            $reconData['affiliate_id']         = $visitor->affiliate_id;
            $reconData['lead_status']  = $visitor->status;
            $reconData['energy_type']          = $product['product_type'];
            $reconData['sale_created']         = $visitor->sale_created;
            $reconData['recon_status']         = 0;
            if ($visitor->sub_affiliate_id != "") {
                $reconData['affiliate_id']         = $visitor->sub_affiliate_id;
                $reconData['parent_id']            = $visitor->affiliate_id;
            }
            array_push($reconTableData, $reconData);
        }

        if (!empty($reconTableData)) {
            ReconSale::insert($reconTableData);
        }
    }


    static function getJourneyDetails($visitID, $columns)
    {
        //$leadData = self::select($columns)->with('energy_lead_jounery', 'energy_bill_details')->get($visitID);
        $leadData =  self::select($columns)
            ->join('lead_journey_data_energy', 'leads.lead_id', '=', 'lead_journey_data_energy.lead_id')
            ->join('energy_bill_details', 'energy_bill_details.lead_id', '=', 'lead_journey_data_energy.lead_id')
            ->join('visitors', 'leads.visitor_id', '=', 'visitors.id')->where('leads.lead_id', $visitID)
            ->first();
        dd($leadData);
    }

    static function saveMobileJourneyData($request)
    {

        $visitId = decryptGdprData($request->visit_id);
        $leadJourneyData = [];
        $leadJourneyData['connection_type'] = $request->connection_type;
        $leadJourneyData['lead_id'] = $visitId;
        if($request->has('current_provider') && $request->current_provider != ''){
            $leadJourneyData['current_provider'] = $request->current_provider;
        }
        $leadJourneyData['plan_type'] = $request->plan_type;
        $leadJourneyData['plan_cost_min'] =$request->plan_cost_min;
        $leadJourneyData['plan_cost_max'] =$request->plan_cost_max;
        $leadJourneyData['data_usage_min'] =$request->data_usage_min;
       // $leadJourneyData['sim_type'] =$request->sim_type;
        LeadJourneyDataMobileHandset::where('lead_id',$visitId)->delete();
        if($request->has('handsets')&& count($request->handsets)){
            // LeadJourneyDataMobileHandset::where('lead_id',$visitId)->delete();

            foreach ($request->handsets as $hId) {
              
                $vData['handset_id'] = $hId['handset_id'];
                $vData['variant_id'] = $hId['variant_id'];
                $vData['lead_id'] = $visitId;
                $varientData[] = $vData;
            }
            LeadJourneyDataMobileHandset::insert($varientData);
        }
        if (!empty($leadJourneyData)) {
            return self::updateOrCreate(['lead_id' => $visitId], $leadJourneyData);
        }
    }

    static function getMobileLeadData($leadId){

        $leadData =  self::select('lead_id','connection_type','plan_type','current_provider','plan_cost_min','plan_cost_max','data_usage_min')->where('lead_id',$leadId)->with('getMobileLeadHandsetData')->first();
        if($leadData){
            return $leadData;
        }
        return [];
        
     }

    /**
     * Check for duplicate lead for all vertical.
     *
     * @return boolean
     */
    static function checkDuplicateForAllVerticals($phone, $email, $visitor)
    {
        $duplicateVerticalWise = [];
        $checkVisitor = DB::table('leads')
            ->join('visitors', 'leads.visitor_id', '=', 'visitors.id')
            ->leftjoin('sale_products_energy', 'leads.lead_id', '=', 'sale_products_energy.lead_id')
            ->leftjoin('sale_products_mobile', 'leads.lead_id', '=', 'sale_products_mobile.lead_id')
            ->leftjoin('sale_products_broadband', 'leads.lead_id', '=', 'sale_products_broadband.lead_id')
            ->where(function ($check) use ($email, $phone) {
                $check->where('email', encryptGdprData($email))
                    ->orWhere('phone', encryptGdprData($phone));
            })
            ->where(function ($query) {
                $query->where('sale_products_energy.id', '!=', null)
                    ->orWhere('sale_products_mobile.id', '!=', null)
                    ->orWhere('sale_products_broadband.id', '!=', null);
            })
            ->where('leads.lead_id', '!=', $visitor->lead_id);

        if ($visitor->affiliate_id) {
            $checkVisitor->where('leads.affiliate_id', $visitor->affiliate_id);
        }
        $leadData = $checkVisitor->select('sale_products_energy.id as energy', 'sale_products_mobile.id as mobile', 'sale_products_broadband.id as broadband')->get();

        if ($leadData->isEmpty()) {
            return false;
        }
        $services = Service::getServices(auth()->user()->id);
        $leadData = $leadData->toArray();
        foreach ($services as $service) {
            $slug = strtolower($service->service_title);
            $duplicateVerticalWise[$slug] = false;
            foreach ($leadData as $lead) {
                if (isset($lead->{$slug}) && $lead->{$slug}) {
                    $duplicateVerticalWise[$slug] = true;
                }
            }
        }
        return $duplicateVerticalWise;
    }

    /**
     * Check for duplicate lead specific to particular vertical.
     *
     * @return boolean
     */
    static function checkDuplicateLead($visitor, $service = 'energy')
    {
        $checkVisitor = DB::table('leads')
            ->join('visitors', 'leads.visitor_id', '=', 'visitors.id')
            ->join('sale_products_' . $service, 'leads.lead_id', '=', 'sale_products_' . $service . '.lead_id')
            ->where(function ($check) use ($visitor) {
                $check->where('email', $visitor->email)
                    ->orWhere('phone', $visitor->phone);
            })
            ->where('sale_products_' . $service . '.id', '!=', null)
            ->where('leads.lead_id', '!=', $visitor->lead_id);

        if ($visitor->affiliate_id) {
            $checkVisitor->where('leads.affiliate_id', $visitor->affiliate_id);
        }

        return $checkVisitor->exists();
    }

    static function addAddress ($request) {
       
        if (!$request->has('address')) return false;

        $addressData = [];
        foreach(VisitorAddress::$gnfMapping as $requestField => $dbField) {
            
            if (isset($request->address[$requestField])) {
                $addressData[$dbField] = $request->address[$requestField];
            }
        }
        
        if (!empty($addressData)) {
            $addressData['address_type'] = 1;
            return VisitorAddress::create($addressData);
        }
        return false;
    }
}
