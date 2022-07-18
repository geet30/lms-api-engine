<?php

namespace App\Traits\Sms;

use Illuminate\Support\Facades\{DB, Crypt};
use App\Models\{LeadConfirmation, SmsLog};
use App\Repositories\SparkPost\NodeMailer;

/**
 * SMS methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    protected $productData, $emailData, $user, $visitor, $service, $affiliate, $attributes, $request;
    /**
     * Construct for email methods.
     *
     * @param mixed $user.
     * @param mixed $productData.
     * @param mixed $visitor.
     * @param mixed $emailData.
     */
    public function __construct($user, $productData, $visitor, $request, $service, $attributes, $affiliate, $emailData = null)
    {
        $this->productData = $productData;
        $this->emailData = $emailData;
        $this->user = $user;
        $this->visitor = $visitor;
        $this->request = $request;
        $this->service = $service;
        $this->attributes = $attributes;
        $this->affiliate = $affiliate;
    }

    public function energySms()
    {
        $sms_message = $provider_name = $gas_provider_name = $elec_provider_name = $message = '';
        $sale_reference_number = $gas_sale_reference_number = $elec_sale_reference_number = '';
        $visitor_email  = $visitor_phone  = $visitor_name   = $visitor_suburb = $visitor_state  = $visitor_postcode = '';

        if (!$this->productData->isEmpty()) {
            $commonData = $this->productData->first();
            $elecData = $this->productData->where('product_type', 1)->first();
            $gasData = $this->productData->where('product_type', 2)->first();
            $visitor_email  = $this->visitor->email;
            $visitor_phone  = $this->visitor->phone;
            $visitor_name   = $this->visitor->first_name . ' ' . $this->visitor->last_name;
            $visitor_suburb = $this->visitor->suburb;
            $visitor_state  = $this->visitor->state;
            $visitor_postcode = $this->visitor->postcode;
            if ($elecData) {
                $elec_provider_name  = $elecData['provider']['legal_name'];
                $provider_name       = $elecData['provider']['legal_name'];
                $sale_reference_number = $elecData['reference_no'];
                $elec_sale_reference_number = $elecData['reference_no'];
            }
            if ($gasData) {
                $gas_provider_name  = $gasData['provider']['legal_name'];
                $provider_name      = $gasData['provider']['legal_name'];
                $sale_reference_number = $gasData['reference_no'];
                $gas_sale_reference_number = $gasData['reference_no'];
            }
            
            $sale_type = 'sms';
            $hash = Crypt::encrypt($commonData['lead_id'] . '|' . $sale_type);
            $unsubscribe_id = str_replace(' ', '', (strtr($hash, '=', ' ')));
            $hash = str_replace(' ', '', (strtr($hash, '=', ' ')));
            $affiliateKey  = DB::table('affiliate_keys')->select('api_key', 'page_url')->where('user_id', $this->user->id)->first();
            $affiliateappurl = $affiliateKey->page_url;

            //unsubscribe url  - use affiliate dedicated url
            $econnex_url = parse_url($affiliateappurl);

            $unsubscribe_url = $econnex_url['scheme'] . '://' . $econnex_url['host'];

            if (isset($econnex_url['path']))
                $unsubscribe_url .= $econnex_url['path'];

            $unsubscribe_url .= '/unsubscribe/?token=' . $unsubscribe_id;

            //remarketing url - use affiliate dedicated url
            $pageUrl = $affiliateappurl . '/?marketingnumber=' . $hash;
            if (!empty($visitor_provider['customer_user_id'])) {
                $pageUrl .= '&cui=' . $visitor_provider['customer_user_id'];
            }


            if (!empty($visitor_provider['sub_affiliate_referralcode'])) {
                $pageUrl .= '&rc=' . $visitor_provider['sub_affiliate_referralcode'];
            }

            $node = new NodeMailer;
            if ($this->emailData->source_type == 1) {
                $pageUrl = $node->bitlyUrlApi($pageUrl);
            } else {
                $pageUrl = $node->rebrandlyUrl($pageUrl);
            }

            config(['plivo.source-number' => $this->emailData->sender_id]);
            $senderId = $this->emailData->sender_id;
            // Sender ID for plivo functionality
            if ($this->emailData->sender_id_method == 'default') {
                $senderId = $this->affiliate->sender_id;
                config(['plivo.source-number' => $this->affiliate->sender_id]);
            }
            $message = "Thank you for registration";
            if (!empty($this->emailData->content)) {
                $sms_message = $this->emailData->content;
                //Replace variables with values from message
                $message  = str_replace("{{{customer_email}}}", $visitor_email, $sms_message);
                $message  = str_replace("{{{customer_number}}}", $visitor_phone, $message);
                $message  = str_replace("{{{customer_name}}}", $visitor_name, $message);
                $message  = str_replace("{{{Suburb}}}", $visitor_suburb, $message);
                $message  = str_replace("{{{Provider-Name}}}", $provider_name, $message);
                $message  = str_replace("{{{State}}}", $visitor_state, $message);
                $message  = str_replace("{{{Postcode}}}", $visitor_postcode, $message);
                $message  = str_replace("{{{Electricity-Provider-Name}}}", $elec_provider_name, $message);
                $message  = str_replace("{{{Gas-Provider-Name}}}", $gas_provider_name, $message);
                $message  = str_replace("{{{Sale-Reference-Number}}}", $sale_reference_number, $message);
                $message  = str_replace("{{{Gas-Sale-Reference-Number}}}", $gas_sale_reference_number, $message);
                $message  = str_replace("{{{Electricity-Sale-Reference-Number}}}", $elec_sale_reference_number, $message);
                $message  = str_replace("{{{Remarketing-link}}}", $pageUrl, $message);
            }
            $smsType = 'plivo';
            
            $msgResponse = $this->createSms($senderId, $visitor_phone, $message, $smsType, $commonData['lead_id']);
            if ($msgResponse) {
                if ($this->emailData->email_type == 1) {
                    if ($elecData) {
                        LeadConfirmation::updateOrCreate(['lead_id' => $commonData['lead_id'], 'product_id' => $elecData['product_id']], ['sms_sent' => 1, 'type' => 1]);
                    }

                    if ($gasData) {
                        LeadConfirmation::updateOrCreate(['lead_id' => $commonData['lead_id'], 'product_id' => $gasData['product_id']], ['sms_sent' => 1, 'type' => 1]);
                    }
                }
            }
        }
    }

    public function createSms($senderId, $destination, $message, $smsType, $leadId)
    {
        $request = request();
        try {
            // if request number exists in temp array then return true.
            if (in_array($destination, config('numbers.temporary_mobile_numbers'))) {
                return ['status' => true, 'response' => 'number in saved in preserved list.'];
            }
            $envoirnment = env('ENVIRONMENT', 'production');
            if (!$request->has('otp_type')) {
                $destination = decryptGdprData($destination);
            }
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
          
            $pivilioResponse = loginTokenx($request, $senderId, $num, $message, $smsType);
            $errorCodeArray = [400, 401, 404, 405, 500];
            
            //when there is an error sending sms using plivo, then send message using twilio
            if (isset($pivilioResponse['status']) && in_array($pivilioResponse['status'], $errorCodeArray)) {
                $reason = 'There is an Exception.';
                if (isset($pivilioResponse['response']['error'])) {
                    $reason = $pivilioResponse['response']['error'];
                }
            }
            $sendSms = [];
            $sendSms['lead_id']   = $leadId;
            $sendSms['user_id']   = $this->user->id;
            $sendSms['service_id']   = $this->service;
            $sendSms['message']   = $message;
            $sendSms['phone'] = $destination;
            $sendSms['sender_id']   = $senderId;
            $sendSms['template_name'] = $this->emailData->template_name;
            $sendSms['message_source'] = "plivo";
            $sendSms['response'] = json_encode($pivilioResponse);
            $sendSms['sms_status'] = $pivilioResponse['status'];
            SmsLog::create($sendSms);
            if (isset($reason)) {
                return ['status' => false, 'response' => $reason];
            }
            return ['status' => true, 'response' => $pivilioResponse];
        } catch (\Exception $e) {
            return errorResponse($e->getMessage(), HTTP_STATUS_SERVER_ERROR, 2002, __FUNCTION__);
        }
    }
}
