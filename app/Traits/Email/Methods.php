<?php

namespace App\Traits\Email;

use Illuminate\Support\Facades\{Storage, DB, Auth};
use App\Repositories\SparkPost\NodeMailer;
use App\Models\{EmailTemplate, Lead};
use App\Traits\Email\Exception;

/**
 * Email methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
    use Exception;

    protected $productData, $emailData, $user, $visitor, $service, $affiliate, $attributes, $request, $refNo;
    /**
     * Construct for email methods.
     *
     * @param mixed $user.
     * @param mixed $productData.
     * @param mixed $visitor.
     * @param mixed $emailData.
     */
    public function __construct($user, $productData, $visitor, $request, $service, $attributes, $affiliate, $emailData = null, $refNo = null)
    {
        $this->productData = $productData;
        $this->emailData = $emailData;
        $this->user = $user;
        $this->visitor = $visitor;
        $this->request = $request;
        $this->service = $service;
        $this->attributes = $attributes;
        $this->affiliate = $affiliate;
        $this->refNo = $refNo;
    }


    public function energyMail()
    {
        $user = $this->user;
        $sparkPostArray = [];
        $sparkPostArray["base_url"] = url();
        $sparkPostArray['affiliate_name'] = decryptGdprData($this->affiliate->legal_name);
        $sparkPostArray['affiliate_logo'] = url('/uploads/profile_images/' . $user->photo);
        $sparkPostArray['affiliate_contact_us'] = decryptGdprData($this->affiliate->support_phone_number);
        $sparkPostArray['affiliate_address'] = $this->affiliate->address;
        $sparkPostArray['youtube'] = $this->affiliate->youtube_url;
        $sparkPostArray['twitter'] = $this->affiliate->twitter_url;
        $sparkPostArray['facebook'] = $this->affiliate->facebook_url;
        $sparkPostArray['linkedin'] = $this->affiliate->linkedin_url;
        $sparkPostArray['google_plus'] = $this->affiliate->google_url;
        $full_name = $this->visitor->first_name . ' ' . $this->visitor->last_name;

        $elecData = $this->productData->where('product_type', 1)->first();
        $gasData = $this->productData->where('product_type', 2)->first();
        if (!empty($this->visitor->middle_name)) {
            $full_name = $this->visitor->first_name . ' ' . $this->visitor->middle_name . ' ' . $this->visitor->last_name;
        }

        $html = '';
        if (!empty($this->emailData)) {
            $html = $this->emailData->content;
        }

        $sparkPostArray['customer_name'] = $full_name;

        $sparkPostArray['customer_email'] = $this->visitor->email;
        $sparkPostArray['suburb_address'] = $this->visitor->address;
        $sparkPostArray['customer_property_address'] = $this->visitor->address;
        $sparkPostArray['customer_billing_address'] = 'N/A';

        if ($this->visitor->billing_address_id) {
            $billingDetails = DB::table('visitor_addresses')->select('address')->where('id', $this->visitor->billing_address_id)->first();
            if ($billingDetails) {
                $sparkPostArray['customer_billing_address'] = $billingDetails->address;
            }
        }

        $elec_provider_id = 0;
        $gas_provider_id = 0;
        $elec_ref = '';
        $gas_ref = '';
        $welcomeEmailSubject = "";;
        if ($this->emailData && $this->emailData->subject)
            $welcomeEmailSubject = $this->emailData->subject;

        $phone = null;
        if ($elecData) {
            $providerUserData = DB::table('users')->select('phone')->find($elecData['provider_id']);
            $phone = $providerUserData ? decryptGdprData($providerUserData->phone) : '';
            $elec_provider_id = $elecData['provider_id'];
            $provider_name = $elecData['provider']['legal_name'];

            if (isset($this->request->reference_number) && !empty($this->request->reference_number)) {
                $elec_ref = $this->request->reference_number;
                $welcomeEmailSubject = $welcomeEmailSubject . ' -  Reference Number:' . $elec_ref;
            }

            $elecData['plan_document'] = isset($elecData['plan_energy']) ? $elecData['plan_energy']['plan_document'] : '#';

            $sparkPostArray['electricity_provider_phone_number'] = $phone;
            $sparkPostArray['electricity_reference_number'] = $elec_ref;
            $sparkPostArray['electricity_plan_name'] = isset($elecData['plan_energy']) ? $elecData['plan_energy']['name'] : '';

            // Price Fact Sheet Function In Welcome Mail
            $sparkPostArray['electricity_plan_detail_link'] = 'Available On Request';
            if (isset($elecData['plan_energy']) && $elecData['plan_energy']['show_price_fact'] == 'yes') {
                $path = 'Providers_Plans' . '/' . str_replace(' ', '_', $provider_name) . '/' . str_replace(' ', '_', $elecData['plan_energy']['name']) . '/' . $elecData['plan_energy']['plan_document'];
                $disk = Storage::disk('s3_plan');

                $url = $disk->getAdapter()->getClient()->getObjectUrl(config('filesystems.disks.s3_plan.bucket'), $path);

                $sparkPostArray['electricity_plan_detail_link'] = '<a href=' . $url . '>Plan Details</a>';
            }
            $sparkPostArray['electricity_provider_name'] = $provider_name;

            $provider_name = preg_replace("/\s+/", "", trim($provider_name));
            $parameter = str_replace(" ", "", $provider_name);
            $sparkPostArray['electricity_provider_term_conditions'] = $this->affiliate->page_url . '/provider-term-conditions/?provider=' . $parameter;
        }

        if ($gasData) {
            if ($elecData && $gasData && $elecData['provider_id'] == $gasData['provider_id'] && $phone) {
                $sparkPostArray['gas_provider_phone_number'] = $phone;
            } else {
                $providerUserData = DB::table('users')->select('phone')->find($elecData['provider_id']);
                $phone = $providerUserData ? decryptGdprData($providerUserData->phone) : '';
                $sparkPostArray['gas_provider_phone_number'] = $phone;
            }
            $gasData['plan_document'] = $gasData['plan_energy']['plan_document'] ?? '#';
            $gas_provider_id = $gasData['provider_id'];
            $provider_name = $elecData['provider']['legal_name'];
            $plan_name = $gasData['plan_energy']['name'];
            if (isset($this->request->reference_number2) && !empty($this->request->reference_number2)) {
                $welcomeEmailSubject = $welcomeEmailSubject . ' -  Reference Number:' . $gas_ref;
                $gas_ref = $this->request->reference_number2;
            } else {
                $welcomeEmailSubject = $welcomeEmailSubject . ' -  Reference Number:' . $gas_ref;
                $gas_ref = $this->request->reference_number;
            }
            $sparkPostArray['gas_reference_number'] = $gas_ref;
            $sparkPostArray['gas_plan_name'] = $plan_name;
            $sparkPostArray['gas_plan_detail_link'] = 'Available On Request';
            if ($gasData['plan_energy']['show_price_fact'] == 'yes') {
                $path = 'Providers_Plans' . '/' . str_replace(' ', '_', $provider_name) . '/' . str_replace(' ', '_', $plan_name) . '/' . $this->plan['gas']->plan_document;
                $disk = \Storage::disk('s3_plan');

                $url = $disk->getAdapter()->getClient()->getObjectUrl(config('filesystems.disks.s3_plan.bucket'), $path);

                $sparkPostArray['gas_plan_detail_link'] = '<a href=' . $url . '>Plan Details</a>';
            }

            $sparkPostArray['gas_provider_name'] = $provider_name;

            $provider_name = preg_replace("/\s+/", "", trim($provider_name));
            $parameter = str_replace(" ", "", $provider_name);
            $sparkPostArray['gas_provider_term_conditions'] = $this->affiliate->page_url . '/provider-term-conditions/?provider=' . $parameter;
        }

        if ((!empty($elec_provider_id) && !empty($gas_provider_id)) && ($elec_provider_id == $gas_provider_id)) {
            $welcomeEmailSubject = $welcomeEmailSubject . ' - Electricity & Gas Confirmation - Reference Number:' . $gas_ref;
        }
        if ((!empty($elec_provider_id) && !empty($gas_provider_id)) && ($elec_provider_id != $gas_provider_id)) {
            $welcomeEmailSubject = $welcomeEmailSubject . ' - Electricity & Gas Confirmation - Electricity Reference Number:' . $elec_ref . ' - Gas Reference Number:' . $gas_ref;
        }
        $subAccountData = DB::table('affiliate_third_party_apis')->select('subaccount_id')->where('user_id', $this->user->id)->first();

        $mailData = [];
        $mailData['template_id'] = $this->emailData->template_id;
        $mailData['subaccount_id'] = $subAccountData ? $subAccountData->subaccount_id : '';
        $mailData['from_email'] = $this->emailData->from_email ?? 'support@cimet.com.au';
        $mailData['from_name'] = $this->emailData->from_name ?? 'CIMET Support Team';
        $mailData['service_id'] = 1;
        $mailData['subject'] = str_replace('_', ' ', $this->emailData->subject);
        $mailData['cc_mailID'] = [];
        $mailData['bcc_mailID'] = [];
        $mailData['attachments'] = [];
        $mailData['mail_data'] = $sparkPostArray;
        $mailData['receiver_email'] = strtolower($this->visitor->email) ?? '';
        $nodeMailer = new NodeMailer();

        return $nodeMailer->sendMailWithTemplate($mailData);
    }

    public function mobileMail()
    {
        $productData = $this->productData->first();
        try {
            $attributes = [];
            $nextParameter = [];
            $html = $dedicated_page = '';
            if (!empty($this->emailData)) {
                $html = $this->emailData->content;
            }
            $attributes = [];
            if (!$this->attributes->isEmpty()) {
                $attributes = $this->attributes->pluck('attribute')->toArray();
            }

            if ($this->service == 3) {
                $refNo = isset($this->refNo[$this->service]) ? $this->refNo[$this->service] : '';
                if ($this->affiliate) {
                    $dedicated_page = $this->affiliate->dedicated_page . '/terms-conditions/?provider=' . encryptGdprData($productData['provider_id']);
                }

                $nextParameter = [
                    $this->visitor->first_name . ' ' . $this->visitor->middle_name . ' ' . $this->visitor->last_name,
                    $productData['provider']['legal_name'],
                    $productData['plan_broadband']['name'],
                    'test test test test Critical-Information-Summary',
                    $dedicated_page,
                    $productData['plan_broadband']['nbn_key_url'],
                    $refNo,
                    $this->visitor->address,
                ];
            }

            $nextParameter = $correctParameters = [];
            if ($this->service == 2) {
                $refNo = isset($this->refNo[$this->service]) ? $this->refNo[$this->service] : '';
                $nextParameter['handset_name'] = '';
                $nextParameter['plan_name'] = $productData['plan_mobile']['name'];
                $nextParameter['reference_number'] = $refNo;
                $nextParameter['variant_name'] = '';
                $nextParameter['RAM'] = '';
                $nextParameter['internal_storage'] = '';
                $nextParameter['color'] = '';
                $nextParameter['SignUp_Plan_Detail_Link'] = $this->generatePlanDetailLink();
                $nextParameter['customer_name'] = $this->visitor->first_name . ' ' . $this->visitor->middle_name . ' ' . $this->visitor->last_name;
                $nextParameter['provider_name'] = $productData['provider']['legal_name'];
                $nextParameter['Critical_Information_Summary'] = '';
                $nextParameter['Terms_and_Conditions'] = '';
                $nextParameter['twitter'] = '';
                $nextParameter['facebook'] = '';
                if ($productData['product_type'] == 2) {
                    $nextParameter['handset_name'] = $productData['handset']['name'];
                    $nextParameter['variant_name'] = $productData['variant']['variant_name'];
                    $nextParameter['RAM'] = $productData['variant']['capacity']['capacity_name'];
                    $nextParameter['internal_storage'] = $productData['variant']['internal']['storage_name'];
                    $nextParameter['color'] = $productData['variant']['color']['title'];
                    $nextParameter['contract_term'] = $productData['contract']['contract_name'];
                }
            }
            $nextKeys = array_keys($nextParameter);
            foreach ($attributes as $attribute) {
                foreach ($nextKeys as $nextKey) {
                    if (str_contains($attribute, $nextKey)) {
                        $correctParameters[$nextKey] = $nextParameter[$nextKey];
                    }
                }
            }

            $html = str_replace($attributes, $correctParameters, $html);

            $mailData = [];
            $mailData['text'] = '';
            $mailData['from_email'] = $this->emailData->from_email ?? 'support@cimet.com.au';
            $mailData['from_name'] = $this->emailData->from_name ?? 'CIMET Support Team';
            $mailData['service_id'] = 1;
            $mailData['subject'] = str_replace('_', ' ', $this->emailData->subject);
            $mailData['cc_mail'] = [];
            $mailData['bcc_mail'] = [];
            $mailData['html']  = $html;
            $mailData['user_email'] = strtolower($this->visitor->email) ?? '';
            $nodeMailer = new NodeMailer();
            $senderId = $this->emailData->sender_id;
            // Sender ID for plivo functionality
            if ($this->emailData->sender_id_method == 'default') {
                $senderId = $this->affiliate->sender_id;
                config(['plivo.source-number' => $this->affiliate->sender_id]);
            }
            $smsType = 'plivo';
            $mailObj = $nodeMailer->sendMail($mailData);
            $this->addLogs($mailObj, $productData);
            $this->createSms($senderId, $this->visitor->phone, $html, $smsType, $productData['lead_id']);
            return $mailObj;
        } catch (\Exception $e) {
            $msg = $e->getMessage() . '  Line no:' . $e->getLine() . '  File:' . $e->getFile();
            $this->addLogs(['message' => $msg], $productData);
            return false;
        }
    }

    public function generatePlanDetailLink()
    {
        $affParam = DB::table('affiliate_paramters')->select('plan_detail','slug')->where('user_id', $this->user->id)->where('service_id', $this->service)->first();
        $slug = $pd = '';
        if ($affParam) {
            $slug = $affParam->slug;
            $pd = $affParam->plan_detail;
        }
        
        $productData = $this->productData->first();
        $leadId = $productData['lead_id'];
        
        $baseUrl = $this->affiliate->page_url;
        $pdBaseUrl = $baseUrl.$slug.'/?'.$pd;

        $signUpPlanDetailLink =  $this->service . "__" . ($leadId) . "__" . $productData['plan_id'];

        $signUpPlanDetailLink = $pdBaseUrl . "=" . base64_encode($signUpPlanDetailLink);

        $signUpPlanDetailLink .= "&rc=" . $this->affiliate->rc_code;
        return $signUpPlanDetailLink;
    }

    public function addLogs($mailObj, $productData)
    {
        $apiRefNo = '';
        $message = 'Something went wrong with welcome email';
        if (is_object($mailObj)) {
            $apiRefNo = $mailObj->data->results->id;
            $message = 'Welcome email sent successfully';
            $mailObj = json_encode($mailObj);
        }
        
        if (is_array($mailObj)) {
            $mailObj = json_encode($mailObj);
        }

        $data = [];
        $data['lead_id'] = isset($productData['lead_id']) ? $productData['lead_id'] : null;
        $data['sale_product_id'] = isset($productData['product_id']) ? $productData['product_id'] : null;
        $data['service_id'] = $this->service;
        $data['api_name'] = 'Welcome email';
        $data['api_reference'] = $apiRefNo;
        $data['response_text'] = $mailObj;
        $data['api_response'] = $mailObj;
        $data['message'] = $message;
        return DB::table('api_responses')->insert($data);
    }
}
