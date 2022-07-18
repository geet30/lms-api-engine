<?php

namespace App\Jobs;



use Illuminate\Support\Facades\DB;
use App\Models\{Lead, Visitor};
use App\Traits\Email\ { Methods };
use App\Traits\Sms\ { Methods as SmsMethods };

class SendWelcomeMail extends Job
{
    use Methods {
        Methods::__construct as private __emailConstruct;
    }
    use SmsMethods {
        SmsMethods::__construct as private __smsConstruct;
    }
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $productData, $emailData, $user, $visitor, $service, $affiliate, $attributes, $request, $refNo;

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
    public function __construct($user, $productData, $visitor, $affiliate, $attributes, $emailData = null, $refNo = null)
    {
        $this->productData = $productData;
        $this->emailData = $emailData;
        $this->user = $user;
        $this->visitor = $visitor;
        $this->affiliate = $affiliate;
        $this->attributes = $attributes;
        $this->refNo = $refNo;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('started' . date("d-m-Y h:i:s"));
        $this->sendWelcomeMail();
        \Log::info('10 seconds completed: ' . date("d-m-Y h:i:s"));
    }

    /**
     * Construct for send email job.
     * Author: Sandeep Bangarh
     * @return mixed
     */
    public function sendWelcomeMail()
    {
        if (empty($this->emailData)) return false;
        $service = Lead::getService(true);
        $this->request = app('request');
        $this->service = $service;
        $this->visitor = Visitor::removeGDPR($this->visitor);
        $this->__emailConstruct($this->user, $this->productData, $this->visitor, $this->request, $this->service, $this->attributes, $this->affiliate, $this->emailData, $this->refNo);
        $this->__smsConstruct($this->user, $this->productData, $this->visitor, $this->request, $this->service, $this->attributes, $this->affiliate, $this->emailData, $this->refNo);
        
        if ($service != 1) {
            return $this->mobileMail();
        }

        return $this->energyMail();
    }
}
