<?php

namespace App\Traits\CommonApi;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\{SaleProductsBroadband, MobileConnectionDetails, BroadbandConnectionDetails, Providers, Affiliate, Otp, Lead, Visitor, LeadEmploymentDetails, SaleBusinessDetail};
use Illuminate\Support\Facades\Auth;


trait BasicCrudMethods
{
    /**
     * Author:Harsimran(16-March-2022)
     * get Move in date data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static public function getMinSelectableDate($request)
    {
        try {
            //get state from full address
            $state = explode(',', $request->input('post_code'));
            //get all national holidays and selected state holidays also
            $holidays = DB::table('move_in_calender')->where('holiday_type', 3)->orWhere(function ($q) use ($state) {
                $q->where('holiday_type', 1)->where('state', trim($state[2]));
            })->pluck('date');
            //get master day closing time
            $day_closing_time = DB::table('settings')->where('key', 'move_in_closing_time')->value('value');
            //enter here if current time passed the master closing time
            $add_days = Carbon::now()->addDay();
            $min_selectable_date = $add_days->toDateString();
            //keep on checking for holiday
            $ahead_days = 1;
            $required_ahead_days = 0;
            $time_now = Carbon::now();
            if ($day_closing_time) {
                $time_with_compare = Carbon::parse($time_now->format('Y-m-d') . ' ' . $day_closing_time);
                if ($time_now > $time_with_compare) {
                    while ($required_ahead_days < 2) {
                        //if min selectable date is a holiday add 1 more day
                        if (in_array($min_selectable_date, $holidays->toArray()) || $add_days->isWeekend()) {
                            $ahead_days++;
                        } else {
                            $ahead_days++;
                            $required_ahead_days++;
                        }
                        $time_now = Carbon::now();
                        $add_days = $time_now->addDays($ahead_days);
                        $min_selectable_date = $add_days->toDateString();
                    }
                    $add_days = Carbon::now()->addDays($ahead_days - 1);
                    $min_selectable_date = $add_days->toDateString();
                    $response = ['status' => 1, 'message' => 'Selectable date calculated successfully', 'selectable_date' => $min_selectable_date];
                } else {
                    while ($required_ahead_days < 2) {
                        //if min selectable date is a holiday add 1 more day
                        if (in_array($min_selectable_date, $holidays->toArray()) || $add_days->isWeekend()) {
                            $ahead_days++;
                        } else {
                            $ahead_days++;
                            $required_ahead_days++;
                        }
                        $time_now = Carbon::now();
                        $add_days = $time_now->addDays($ahead_days - 1);
                        $min_selectable_date = $add_days->toDateString();
                    }

                    $time_now = Carbon::now();
                    $add_days = $time_now->addDays($ahead_days - 2);
                    $min_selectable_date = $add_days->toDateString();

                    $response = ['status' => 1, 'message' => 'Selectable date calculated successfully', 'selectable_date' => $min_selectable_date];
                }
            } else {
                //enter here if current time not passed the master closing time
                $add_days = Carbon::now();
                $min_selectable_date = $add_days->toDateString();

                //keep on checking for holiday
                $ahead_days = 1;
                $required_ahead_days = 0;
                while ($required_ahead_days < 2) {
                    //if min selectable date is a holiday add 1 more day
                    if (in_array($min_selectable_date, $holidays->toArray()) || $add_days->isWeekend()) {
                        $ahead_days++;
                    } else {
                        $ahead_days++;
                        $required_ahead_days++;
                    }
                    $time_now = Carbon::now();
                    $add_days = $time_now->addDays($ahead_days - 1);
                    $min_selectable_date = $add_days->toDateString();
                }

                $time_now = Carbon::now();
                $add_days = $time_now->addDays($ahead_days - 2);
                $min_selectable_date = $add_days->toDateString();

                $response = ['status' => 1, 'message' => 'Selectable date calculated successfully', 'selectable_date' => $min_selectable_date];
            }

            if (!empty($min_selectable_date)) {
                //get all national holidays and selected state holidays also
                $now_date = Carbon::now()->toDateString();
                $holidays = DB::table('move_in_calender')->where('holiday_type', 2)
                    ->orWhere(function ($x) use ($state, $now_date) {
                        $x->where(function ($inner) use ($state) {
                            $inner->where('holiday_type', 3)
                                ->orWhere(function ($q) use ($state) {
                                    $q->where('holiday_type', 1)
                                        ->where('state', trim($state[2]));
                                });
                        });
                        $x->where('date', '>=', $now_date);
                    })
                    ->select(DB::raw("DATE_FORMAT(date,'%m/%d/%Y') as date"), 'holiday_title', 'holiday_type', 'holiday_content')->get();

                if ($holidays->count() != 0) {
                    $holidays = $holidays->toArray();
                    $response['holiday_content'] = $holidays;
                    $response['holiday_status'] = 200;
                } else {
                    $response['holiday_content'] = "";
                    $response['holiday_status'] = 200;
                }
            }

            //get moving_days_interval from setting table
            $moving_days_interval = DB::table('settings')->where('key', '=', 'moving_days_interval')->value('value');
            if ($moving_days_interval) {
                $response['moving_days_interval'] = $moving_days_interval;
            } else {
                $response['moving_days_interval'] = 0;
            }

            return $response;
        } catch (\Exception $e) {
        }
    }
    /**
     * Author:Harsimran(28-March-2022)
     * save utm parameter of broadband
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    static function saveRmUtm($request)
    {
        try {
            $visit_id = decryptGdprData($request->visit_id);
            if ($visit_id) {
                if ($request->header('ServiceId') == 3) {
                    $id = SaleProductsBroadband::where('lead_id', $visit_id)->first()->id;
                    if (isset($id)) {
                        $data = [];
                        if ($request->has('utm_rm')) {
                            $data['utm_rm'] = $request->utm_rm;
                        }
                        if ($request->has('utm_rm_source')) {
                            $data['utm_rm_source'] = $request->utm_rm_source;
                        }
                        if ($request->has('utm_rm_date')) {
                            $data['utm_rm_date'] = $request->utm_rm_date;
                        }
                        $response = SaleProductsBroadband::where('lead_id', $visit_id)->update($data);
                        if ($response) {
                            return successResponse("Data has been saved", 2001);
                        }
                        return successResponse("Data is not saved", 2001);
                    }
                    return errorResponse('Id is not found', HTTP_STATUS_NOT_FOUND, 2002);
                }
                return errorResponse('Service Id is not Correct', HTTP_STATUS_NOT_FOUND, 2002);
            }
            return errorResponse('Visit id is not correct', HTTP_STATUS_NOT_FOUND, 2002);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

    /**
     * Author:Harsimran(28-March-2022)
     * save utm parameter of broadband
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    static function getStreetCodes()
    {
        try {
            $response = DB::table('master_street_codes')->get()->toArray();
            return successResponse("Success", 2001, ['street_codes' => $response]);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(25-March-2022)
     * save connection detail data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static function saveConnectionDetails($request)
    {
        try {
            if ($request->service_id == 3) {
                return self::broadbandConnectionDetail($request);
            }
            if ($request->service_id == 2) {
                return self::mobileConnectionDetail($request);
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    static function sendConnectionOtp($request)
    {
        try {
            switch ($request->connection_type_request) {
                case '2':
                    return self::transferConnectionRequestType($request);
                    break;
                case '3':
                    return self::renewConnectionRequestType($request);
                    break;
                default:
                    return array('status' => false, 'message' => 'Connection Type Request not recieved.', 'status_code' => 400);
                    break;
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    /**
     * Author:Harsimran(25-March-2022)
     * save mobile connection detail data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static function mobileConnectionDetail($request)
    {
        try {
            $request->merge(['otp_type' => 'connection']);
            switch ($request->connection_type_request) {
                case '1':
                    return self::newConnectionRequestData($request);
                    break;
                case '2':
                    return self::transferConnectionRequestData($request);
                    break;
                case '3':
                    return self::renewConnectionRequestData($request);
                    break;
                default:
                    return array('status' => false, 'message' => 'Connection Type Request not recieved.', 'status_code' => 400);
                    break;
            }
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    /**
     * Author:Harsimran(25-March-2022)
     * save mobile newconnectionconnection detail data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static public function newConnectionRequestData($request)
    {
        try {
            $record = DB::table('sale_products_mobile')->select('sale_products_mobile.id', 'sale_product_mobile_connection_details.id as connection_id')->leftJoin('sale_product_mobile_connection_details', function ($query) {
                $query->on('sale_product_mobile_connection_details.mobile_connection_id', 'sale_products_mobile.id');
            })->where('lead_id', decryptGdprData($request->visit_id))->get()->toArray();
            if (!empty($record)) {
                if (empty($record[0]->connection_id)) {
                    $data['broadband_connection_id'] = $record[0]->id;
                    $response = MobileConnectionDetails::create([
                        'connection_request_type' => 1,
                        'mobile_connection_id' => $record[0]->id
                    ]);
                } else {
                    $response = MobileConnectionDetails::where('id', $record[0]->connection_id)->update([
                        'connection_request_type' => 1
                    ]);
                }

                if ($response) {
                    return successResponse("Connection Details Saved Successfully.", 2001);
                } else {
                    return successResponse("Connection Details Saved Successfully.", 2001);
                }
            }
            return errorResponse("Record Not found", HTTP_STATUS_SERVER_ERROR, 2002);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(25-March-2022)
     * save mobile transferconnection detail data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static public function transferConnectionRequestData($request)
    {
        try {
            // if (Auth::user()->affiliate->is_port_in == 1) {

            $check = DB::table('sale_products_mobile')
                ->select('sale_products_mobile.id', 'provider_permissions.is_port as provider_port', 'plans_mobile.port_allowed as plan_port', 'providers.legal_name', 'sale_product_mobile_connection_details.id as connection_id')
                ->leftJoin('provider_permissions', 'sale_products_mobile.provider_id', 'provider_permissions.user_id')
                ->leftjoin('plans_mobile', 'sale_products_mobile.plan_id', 'plans_mobile.id')
                ->leftjoin('providers', 'sale_products_mobile.provider_id', 'providers.user_id')
                ->leftJoin('sale_product_mobile_connection_details', 'sale_product_mobile_connection_details.mobile_connection_id', 'sale_products_mobile.id')
                ->where('lead_id', decryptGdprData($request->visit_id))->get()->toArray();

            // if ((!empty($check[0]->provider_port) && $check[0]->provider_port == 1) && (!empty($check[0]->plan_port) && $check[0]->plan_port == 1)) {

            $leads = DB::table('leads')->select('visitors.phone', 'leads.status')->join('visitors', 'visitors.id', 'leads.visitor_id')->get()->toArray();

            $data = [];
            $data['connection_request_type'] = 2;
            $data['current_provider'] = $request->transfer_current_provider;
            $data['connection_phone'] =  $request->transfer_phone;
            $data['connection_account_no'] = $request->transfer_account_number;
            $data['connection_verify_through'] = 'account_no';
            $data['transfer_to_provider'] = $check[0]->legal_name ? $check[0]->legal_name : "";

            if (empty($check[0]->connection_id)) {
                $data['mobile_connection_id'] = $check[0]->id;
                $response = MobileConnectionDetails::create($data);
            } else {
                $response = MobileConnectionDetails::where('id', $check[0]->connection_id)->update($data);
            }
            if ($response) {
                // $sale = [
                //     'affiliate_id' => Auth::user()->id,
                //     'connection_phone' => $request->transfer_phone,
                //     'phone' => $leads[0]->phone,
                //     'lead_status' => $leads[0]->status
                // ];
                //$return = self::resendOtpRequest($request, $sale);
                // if ($return['status']) {
                return array('status' => true, 'message' => 'Connection Details Saved Successfully.', 'status_code' => 200);
                // }
                // return array('status' => false, 'message' => 'There is an error while sending message.', 'status_code' => 400);
            }
            return array('status' => false, 'message' => 'Error.', 'status_code' => 400);
            //     } else {
            //         return array('status' => false, 'message' => 'port in not allowed', 'status_code' => 400);
            //     }
            // }
            // return array('status' => false, 'message' => 'Renew is not allowed by Provider.', 'status_code' => 400);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(25-March-2022)
     * save mobile renewconnection detail data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static public function renewConnectionRequestData($request)
    {
        try {
            // if (Auth::user()->affiliate->retension_allow == 1) {

            $check = DB::table('sale_products_mobile')
                ->select('sale_products_mobile.id', 'provider_permissions.is_retention as provider_retention', 'plans_mobile.retention_allowed as plan_retention', 'providers.legal_name', 'sale_product_mobile_connection_details.id as connection_id')
                ->leftJoin('provider_permissions', 'sale_products_mobile.provider_id', 'provider_permissions.user_id')
                ->leftjoin('plans_mobile', 'sale_products_mobile.plan_id', 'plans_mobile.id')
                ->leftjoin('providers', 'sale_products_mobile.provider_id', 'providers.user_id')
                ->leftjoin('sale_product_mobile_connection_details', 'sale_product_mobile_connection_details.mobile_connection_id', 'sale_products_mobile.id')->where('lead_id', decryptGdprData($request->visit_id))->get()->toArray();

            // if ((!empty($check[0]->provider_retention) && $check[0]->provider_retention == 1) && (!empty($check[0]->plan_retention) && $check[0]->plan_retention == 1)) {

            $leads = DB::table('leads')->select('visitors.phone', 'leads.status')->join('visitors', 'visitors.id', 'leads.visitor_id')->get()->toArray();

            $data = [];
            $data['connection_request_type'] = 3;
            $data['current_provider'] = $check[0]->legal_name ? $check[0]->legal_name : "";
            $data['connection_account_no'] = $request->renew_account_number;
            $data['connection_phone'] = $leads[0]->phone;
            if (isset($request->lease_detail) && $request->lease_detail == 1) {
                $data['conn_is_lease'] = 1;
                $data['conn_renew_lease_start_date'] = $request->lease_date;
            } else {
                $data['conn_is_lease'] = 0;
            }
            if (empty($check[0]->connection_id)) {
                $data['mobile_connection_id'] = $check[0]->id;
                $response = MobileConnectionDetails::create($data);
            } else {
                $response = MobileConnectionDetails::where('id', $check[0]->connection_id)->update($data);
            }
            if ($response) {
                // $sale = [
                //     'affiliate_id' => Auth::user()->id,
                //     'connection_phone' =>  $leads[0]->phone,
                //     'phone' => $leads[0]->phone,
                //     'lead_status' => $leads[0]->status
                // ];
                // $return = self::resendOtpRequest($request, $sale);
                // if ($return['status']) {
                return array('status' => true, 'message' => 'Connection Details Saved Successfully.', 'status_code' => 200);
                // }
                // return array('status' => false, 'message' => 'There is an error while sending message.', 'status_code' => 400);
            }
            return array('status' => false, 'message' => 'Error.', 'status_code' => 200);
            //     } else {
            //         return array('status' => false, 'message' => 'Renew is not allowed by Provider.', 'status_code' => 400);
            //     }
            // }
            // return array('status' => false, 'message' => 'Renew is not allowed by Provider.', 'status_code' => 400);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

    static public function renewConnectionRequestType($request)
    {
        try {
            $leads = DB::table('leads')->where('lead_id', decryptGdprData($request->visit_id))->select('visitors.phone', 'leads.status')->join('visitors', 'visitors.id', 'leads.visitor_id')->get()->toArray();

            $sale = [
                'affiliate_id' => Auth::user()->id,
                'connection_phone' =>  decryptGdprData($leads[0]->phone),
                'phone' => decryptGdprData($leads[0]->phone),
                'lead_status' => $leads[0]->status
            ];
            $return = self::resendOtpRequest($request, $sale);
            if ($return['status']) {
                return array('status' => true, 'message' => $return['message'], 'status_code' => 200);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    static public function transferConnectionRequestType($request)
    {
        try {
            $leads = DB::table('leads')->where('lead_id', decryptGdprData($request->visit_id))->select('visitors.phone', 'leads.status')->join('visitors', 'visitors.id', 'leads.visitor_id')->get()->toArray();
            $sale = [
                'affiliate_id' => Auth::user()->id,
                'connection_phone' => $request->transfer_phone,
                'phone' => decryptGdprData($leads[0]->phone),
                'lead_status' => $leads[0]->status
            ];
            $return = self::resendOtpRequest($request, $sale);
            if ($return['status']) {
                return array('status' => true, 'message' => $return['message'], 'status_code' => 200);
            }
            return array('status' => false, 'message' => 'There is an error while sending message.', 'status_code' => 400);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(25-March-2022)
     * save brodband connection detail data
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static function broadbandConnectionDetail($request)
    {
        try {
            /*********** Connection Details ***********/
            $data = [];
            if (isset($request->is_provider)) {
                if ($request->is_provider == 1) {
                    $data['is_provider_account'] = $request->is_provider;
                    $data['provider_account'] = $request->provider_account;
                } else {
                    $data['is_provider_account'] = 0;
                    $data['provider_account'] = '';
                }
            }
            if (isset($request->existing_phone)) {
                if ($request->existing_phone == 1) {
                    $data['is_phone_number'] = $request->existing_phone;
                    $data['home_number'] = $request->home_number;
                    $data['current_account'] = $request->current_account;
                    $data['transfer_service'] = $request->transfer_service;
                } else {
                    $data['is_phone_number'] = 0;
                    $data['home_number'] = '';
                    $data['current_account'] = '';
                    $data['transfer_service'] = 0;
                }
            }
            if (isset($request->is_provider) || isset($request->existing_phone)) {
                $record = DB::table('sale_products_broadband')->select('sale_products_broadband.id', 'sale_product_broadband_connection_details.id as connection_id')->leftJoin('sale_product_broadband_connection_details', function ($query) {
                    $query->on('sale_product_broadband_connection_details.broadband_connection_id', 'sale_products_broadband.id');
                })->where('lead_id', decryptGdprData($request->visit_id))->get()->toArray();

                if (empty($record[0]->connection_id)) {
                    $data['broadband_connection_id'] = $record[0]->id;
                    $response = BroadbandConnectionDetails::create($data);
                } else {
                    $response = BroadbandConnectionDetails::where('id', $record[0]->connection_id)->update($data);
                }
                if ($response) {
                    return successResponse("Connection details has been updated successfully.", 2001);
                } else {
                    return errorResponse('Connection details is not updated.', HTTP_STATUS_SERVER_ERROR, 2002);
                }
            }
            /**************End Connection Details ***********/
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    /**
     * Author:Harsimran(25-March-2022)
     * send opt
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    static public function resendOtpRequest($request, $sale)
    {
        try {
            $number = $sale['phone'];
            if (isset($request->otp_type) && $request->input('otp_type') == 'connection') {
                $number = $sale['connection_phone'];
            }
            $minutes = '30';
            $affilatedata = Affiliate::where('user_id', $sale['affiliate_id'])->first();
            if ($affilatedata) {
                if ($affilatedata->parent_id == 0) {
                    $sender_id = $affilatedata->sender_id;
                } else {
                    $parentId =  $affilatedata->parent_id;
                    $findParent = Affiliate::where('id', $parentId)->first()->toArray();
                    if ($findParent) {
                        $sender_id = $findParent->sender_id;
                    }
                }
            } else {
                $sender_id = 'CIMET';
            }
            // check OTP exists for customer.
            $visitor = Otp::where('lead_id', decryptGdprData($request->visit_id))->first();
            if ($visitor) {
                // check if status is already verified or not.
                if ($visitor->status == 0) {
                    if ($visitor->expires_at == null) {
                        $generated_otp = rand(1000, 9999);
                        $data['expires_at'] = Carbon::now()->addMinutes($minutes);
                    } else {
                        $currentTime = Carbon::now(); // current time
                        if ($currentTime >= $visitor->expires_at) {
                            $generated_otp = rand(1000, 9999);
                            $data['expires_at'] = Carbon::now()->addMinutes($minutes);
                        } else {
                            $generated_otp = $visitor->otp;
                        }
                    }
                    if ($request->service_id == 3) {
                        $serviceType = 'Broadband';
                    } else if ($request->service_id == 2) {
                        $serviceType = 'Mobile';
                    } else {
                        $serviceType = 'Energy';
                    }
                    $message = 'Thank you for choosing us Your unique OTP for ' . $serviceType . ' Comparison is ' . $generated_otp . '. Please enter verification code to complete your ' . $serviceType . ' application.';

                    if ($request->input('resend_sms') == 'true') {
                        $smsType = 'twillio';
                        if (env('resend_sms_gateway') == 1) {
                            $smsType = 'plivo';
                        }
                        $sms_response = self::createSms($request, $sender_id, $number, $message, $smsType);
                        
                    } else {
                        $smsType = 'plivo';
                        if (env('send_sms_gateway') == 2) {
                            $smsType = 'twillio';
                        }
                        $sms_response = self::createSms($request, $sender_id, $number, $message, $smsType);
                    }
                    $data['otp'] = $generated_otp;
                    if ($sms_response['status']) {
                        if (Otp::where('lead_id', decryptGdprData($request->visit_id))->update($data)) {
                            return array('status' => true, 'message' => 'OTP has been sent to your number. Please enter OTP to continue.', 'status_code' => 200);
                        }
                    }
                    return array('status' => false, 'message' => 'OTP not send', 'status_code' => 400);
                } //if visitor status is 1 means already verified
                elseif ($visitor->status == 1) {
                    return array('status' => false, 'message' => 'You have already comfirmed OTP.', 'status_code' => 400);
                }
            } else {
                $generated_otp = rand(1000, 9999);
                $otp = new Otp();
                $otp->lead_id = decryptGdprData($request->visit_id);
                $otp->expires_at = Carbon::now()->addMinutes($minutes);
                $otp->otp = $generated_otp;
                $otp->status = 0;
                if ($request->service_id == 3) {
                    $serviceType = 'Broadband';
                } else if ($request->service_id == 2) {
                    $serviceType = 'Mobile';
                } else {
                    $serviceType = 'Energy';
                }
                $message = 'Thank you for choosing us Your unique OTP for ' . $serviceType . ' Comparison is ' . $generated_otp . '. Please enter verification code to complete your ' . $serviceType . ' application.';
                if ($request->has('service_id')) {
                    if ($request->input('resend_sms') == 'true') {
                        $smsType = 'twillio';
                        if (env('resend_sms_gateway') == 1) {
                            $smsType = 'plivo';
                        }
                        $sms_response = self::createSms($request, $sender_id, $number, $message, $smsType);
                    } else {
                        $smsType = 'plivo';
                        $sms_response = self::createSms($request, $sender_id, $number, $message, $smsType);
                        $sms_responce['response'] = $sms_response;
                        
                    }
                    //return $sms_response;
                } else {
                    $smsType = 'plivo';
                    if (env('send_sms_gateway') == 2) {
                        $smsType = 'twillio';
                    }
                    $sms_response = self::createSms($request, $sender_id, $number, $message, $smsType);
                    
                }
                if ($sms_response['status']) {
                    if ($otp->save()) {
                        return array('status' => true, 'message' => 'OTP has been sent to your number. Please enter OTP to continue.', 'status_code' => 200);
                    } else {
                        return array('status' => false, 'message' => 'OTP not send', 'status_code' => 400);
                    }
                }
                return array('status' => false, 'message' => 'OTP not send', 'status_code' => 400);
            }
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

    static  public function createSms($request, $sender_id, $destination, $message, $smsType)
    {
        try {
            if (!$request->has('otp_type')) {
                $destination = decryptGdprData($destination);
            }
            // if request number exists in temp array then return true.
            if (in_array($destination, config('numbers.temporary_mobile_numbers'))) {
                return ['status' => true, 'response' => 'number in saved in preserved list.'];
            }
            $envoirnment = env('ENVIRONMENT', 'production');
            $num = $destination;
            if ($envoirnment == 'production') {
                if (substr($destination, 0, 1) == 0) {
                    $num = '61' . substr($destination, 1);
                } elseif (substr($destination, 0, 2) == 61) {
                    $num = $destination;
                } else {
                    $num = '61' . $destination;
                }
            }
            $pivilioResponse = loginTokenx($request, $sender_id, $num, $message, $smsType);
            $error_code_Array = [400, 401, 404, 405, 500];
            //when there is an error sending sms using plivo, then send message using twilio
            if (isset($pivilioResponse['status']) && in_array($pivilioResponse['status'], $error_code_Array)) {

                if (isset($pivilioResponse['response']['error'])) {
                    $reason = $pivilioResponse['response']['error'];
                } else {
                    $reason = 'There is an Exception.';
                }
                return ['status' => false, 'response' => $reason];
            }
            return ['status' => true, 'response' => $pivilioResponse];
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }

    static public function savePersonalDetails($request)
    {
        DB::beginTransaction();
        try {
            $serviceId = $request->header('ServiceId');


            $visit_id = decryptGdprData($request->visit_id);
            if ($serviceId == 2) {
                $visitor_data =  DB::table('leads')
                    ->leftJoin('lead_journey_data_mobile', 'leads.lead_id', '=', 'lead_journey_data_mobile.lead_id')
                    ->select('leads.visitor_id', 'lead_journey_data_mobile.connection_type')->where('leads.lead_id', $visit_id)->first();
            } else {
                $visitor_data =  DB::table('leads')
                    ->select('leads.visitor_id')->where('leads.lead_id', $visit_id)->first();
            }
            if ($visitor_data) {
                $data = array();
                $data['title'] = str_replace('.', '', $request->title);
                $nameArr = explode(' ', $request->first_name);
                $firstName = isset($nameArr[0]) ? array_shift($nameArr) : '';
                $user_name = $firstName;
                $midPoints = [];
                for ($i = 0; $i < (count($nameArr)); $i++) {
                    $midPoints[] = $nameArr[$i];
                }
                if (count($midPoints) > 0) {
                    $middleName = trim(implode(' ', $midPoints));
                    $user_name .= " " . $middleName;
                } else {
                    $middleName = '';
                }

                if ($request->last_name) {
                    $user_name .= " " . stripslashes($request->last_name);
                }

                $data['first_name'] = strtolower($firstName);
                $data['middle_name'] = !empty($middleName) ? strtolower($middleName) : '';
                $data['last_name'] = strtolower(stripslashes($request->last_name));
                $data['email'] = strtolower($request->email);
                $data['phone'] = self::modifyPhoneNumber($request->phone);
                $email_domain = explode('@', $request->email);
                $data['domain'] = $email_domain[1] ?? '';
                // check if request contain alternate phone number.
                if ($request->has('alternate_phone')) {
                    $data['alternate_phone'] = $request->alternate_phone;
                }
                //convert dd/mm/yyyy to mm/dd/yyyy   
                $dob = request('dob', null);
                if ($dob) {
                    $dob = explode('/', $dob);
                    $dob = $dob[1] . '/' . $dob[0] . '/' . $dob[2];
                    $data['dob'] = date('Y-m-d', strtotime($dob));
                }
                $data = self::applyGDPR($data);
                if (Visitor::where('id', $visitor_data->visitor_id)->update($data)) {
                    $update_response = true;
                    if (isset($visitor_data->connection_type) && $visitor_data->connection_type !== 1) {

                        $update_response = self::saveBusinessDetails($request, $visit_id);
                    }
                    if ($update_response) {
                        DB::commit();
                        return successResponse("Personal details updated successfully.", 2001);
                    }

                    DB::rollback();
                    return errorResponse('Personal details not updated. Please try again later.', HTTP_STATUS_NOT_FOUND, 2002);
                }
                DB::rollback();
                return errorResponse('Personal details not updated. Please try again later.', HTTP_STATUS_NOT_FOUND, 2002);
            }
            DB::rollback();
            return errorResponse('First apply atleast a plan.', HTTP_STATUS_NOT_FOUND, 2002);
        } catch (\Exception $e) {
            DB::rollback();
            // save logs
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
    public  static function saveBusinessDetails($request, $lead_id)
    {

        try {
            $inputs = $request->all();
            $data = array();
            // set default empty values
            $data['director_title'] = '';
            $data['director_first_name'] = '';
            $data['director_middle_name'] = '';
            $data['director_last_name'] = '';
            $data['director_email'] =  '';
            $data['director_phone'] = '';
            $data['director_dob'] = '';
            // assign values
            $data['business_name'] = isset($inputs['business_name']) ? $inputs['business_name'] : '';
            $data['business_abn'] = isset($inputs['business_abn']) ? $inputs['business_abn'] : '';
            $data['business_postcode'] = isset($inputs['business_postcode']) ? $inputs['business_postcode'] : '';
            $data['year_incorporated'] = isset($inputs['year_incorporated']) ? $inputs['year_incorporated'] : '';
            $data['business_employee'] = isset($inputs['business_employee']) ? $inputs['business_employee'] : 0;
            $data['business_representative'] = isset($inputs['business_representative']) ? $inputs['business_representative'] : 0;
            // if business respresentative is not then take director's data
            if ($data['business_representative'] == 0) {
                $data['director_title'] = isset($inputs['director_title']) ? $inputs['director_title'] : '';
                $data['director_first_name'] = isset($inputs['director_first_name']) ? $inputs['director_first_name'] : '';
                $data['director_middle_name'] = isset($inputs['director_middle_name']) ? $inputs['director_middle_name'] : '';
                $data['director_last_name'] = isset($inputs['director_last_name']) ? $inputs['director_last_name'] : '';
                $data['director_email'] = isset($inputs['director_email']) ? $inputs['director_email'] : '';
                $data['director_phone'] = isset($inputs['director_phone']) ? $inputs['director_phone'] : '';
                $data['director_dob'] = isset($inputs['director_dob']) ? $inputs['director_dob'] : '';
            }

            $result = SaleBusinessDetail::updateOrCreate(['lead_id' => $lead_id], $data);

            if ($result) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            DB::rollback();
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }


    /**
     * modify phone number.
     */
    static public function modifyPhoneNumber($phone)
    {
        if (substr($phone, 0, 1) == 0) {
            $num = $phone;
        } elseif (substr($phone, 0, 2) == 61) {
            $num = substr($phone, 2);
            if (substr($num, 0, 1) != 0)
                $num = '0' . $num;
        } elseif (substr($phone, 0, 1) != 0) {
            $num = '0' . $phone;
        } else {
            $num = $phone;
        }
        return $num;
    }

    /**
     * Author:Harsimran(19-April-2022)
     * save employment details
     * @param  \Illuminate\Http\Request  $request
     * @return array $response
     */
    public static function saveEmploymentDetails($request)
    {
        DB::beginTransaction();
        try {
            $data = $request->employement_details;
            $lead_id = decryptGdprData($request->visit_id);
            $employmentModel = new LeadEmploymentDetails;
            
            LeadEmploymentDetails::where('lead_id', $lead_id)->delete();
            foreach($data as $key => $valArr) {
                foreach ($employmentModel->fillable as $column) {
                    if (!isset($valArr[$column])) {
                        $data[$key][$column] = null;
                    }
                }
                $data[$key]['lead_id'] = $lead_id;
            }
            
            $response = LeadEmploymentDetails::insert($data);
            DB::commit();
            if ($response) {
                return successResponse("Data has been saved successfully.", 2001);
            }
            DB::rollback();
            return errorResponse('Details has not been updated. Please try again later.', HTTP_STATUS_NOT_FOUND, 2002);
        } catch (\Exception $e) {
            DB::rollback();
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ABN_ERROR_CODE, __FUNCTION__);
        }
    }
}
