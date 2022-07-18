<?php

namespace App\Jobs;
use App\Traits\CommonApi\BasicCrudMethods;

class SendSms extends Job
{
    use BasicCrudMethods;
    
    /**
     * Create a new job instance.
     * @var array
     */
    protected $leadData;

    /**
     * Create a new job instance.
     * @param array $leadData
     * @return void
     */
    public function __construct($leadData)
    {
        $this->leadData = $leadData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = self::resendOtpRequest(request(), $this->leadData);
        \Log::info($response);
    }
}
