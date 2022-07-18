<?php

namespace App\Jobs;
use App\Traits\Email\Methods;

class SendExceptionMail extends Job
{
    use Methods;
    
    /**
     * Create a new job instance.
     * @var array
     */
    protected $msg, $method;

    /**
     * Create a new job instance.
     * @param string $msg
     * @param string $method
     * @return void
     */
    public function __construct($msg, $method)
    {
        $this->msg = $msg;
        $this->method = $method;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $response = $this->sendExceptionAlert($this->msg, $this->method);
        \Log::info($response);
    }
}
