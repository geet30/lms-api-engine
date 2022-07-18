<?php

namespace App\Jobs;



use Illuminate\Support\Facades\DB;
use App\Models\{Lead, Visitor};
use App\Traits\Sms\ { Methods };

class SendWelcomeSms extends Job
{
    use Methods {
        Methods::__construct as private __smsConstruct;
    }
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $productData, $emailData, $user, $visitor, $service, $affiliate, $attributes, $request;

    /**
     * Construct for send email job.
     *
     * @param mixed $user.
     * @param mixed $productData.
     * @param mixed $visitor.
     * @param mixed $emailData.
     * @param mixed $affiliate.
     * @param mixed $attributes.
     */
    public function __construct($user, $productData, $visitor, $affiliate, $attributes, $emailData = null)
    {
        $this->productData = $productData;
        $this->emailData = $emailData;
        $this->user = $user;
        $this->visitor = $visitor;
        $this->affiliate = $affiliate;
        $this->attributes = $attributes;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('started' . date("d-m-Y h:i:s"));
        $this->sendWelcomeSms();
        \Log::info('10 seconds completed: ' . date("d-m-Y h:i:s"));
    }

    /**
     * Construct for send email job.
     * Author: Sandeep Bangarh
     * @return mixed
     */
    public function sendWelcomeSms()
    {
        if (empty($this->emailData)) return false;
        $service = Lead::getService(true);
        $this->request = app('request');
        $this->service = $service;
        $this->visitor = Visitor::removeGDPR($this->visitor);
        $this->attributes = DB::table('affiliate_template_attribute')->where('service_id', $this->service)->get();
        $this->affiliate = $this->user->getAffiliate(['abn', 'parent_id', 'legal_name', 'support_phone_number', 'youtube_url', 'twitter_url', 'facebook_url', 'linkedin_url', 'google_url', 'subaccount_id', 'page_url', 'address', 'dedicated_page','sender_id'], true, true, true, true);
        $this->__smsConstruct($this->user, $this->productData, $this->visitor, $this->request, $this->service, $this->attributes, $this->affiliate, $this->emailData);
        if ($service != 1) {
            // return $this->mobileSms();
        }

        return $this->energySms();
    }
}
