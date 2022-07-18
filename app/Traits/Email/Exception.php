<?php

namespace App\Traits\Email;

use Illuminate\Support\Facades\{Storage, DB, Auth};
use App\Repositories\SparkPost\NodeMailer;
use App\Models\{EmailTemplate, Lead};
use Carbon\Carbon;

/**
 * Email methods model.
 * Author: Sandeep Bangarh
 */

trait Exception
{
    public function sendExceptionAlert($msg, $method)
    {
        $headerdata = $this->makeHeaders($msg, $method);
        $service = Lead::getService();
        $mailArr = [];
        $api = '';
        $affilateName = " ";

        if (!isset($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = 'Promotional Cron';
        }
        if (!empty($request))
            $result = json_encode($request->all(), JSON_PRETTY_PRINT);
        else
            $result = 'no request data found';

        if (isset($headerdata['api_key'])) {
            $api = explode("-", $headerdata['api_key']);
            $api = str_replace($api[1], '*****', $api);
            $api = str_replace($api[2], '*****', $api);
            $api = str_replace($api[3], '*****', $api);
            $api = implode("-", $api);
        }
        //get current date and time according to Sydney time
        $date = Carbon::now();
        $user = Auth::user();
        if ($user) {
            $affiliateData = $user->getAffiliate(['company_name']);
            $affilateName = $affiliateData->company_name;
        } elseif (!empty($headerdata['api_key'])) {
            $affiliateData = DB::table('affiliate_keys')->select('company_name')
                ->join('affiliates', 'affiliate_keys.user_id', 'affiliates.user_id')
                ->where("api_key", encryptGdprData($headerdata['api_key']))->orderBy('affiliate_keys.id', 'desc')->first();
            $affilateName = $affiliateData->company_name;
        }

        $fileName = 'Exception_' . uniqid() . '.txt';
        //save all excaption data into txt file    
        Storage::disk('local')->put($fileName, 'Request_data is :' . "\n");
        Storage::append($fileName, $result . "\n");
        Storage::append($fileName, 'Error Message is  :  ' . $headerdata['error_message'] . "\n");
        if (isset($headerdata['status'])) {
            Storage::append($fileName, 'status  :  ' . $headerdata['status'] . "\n");
        }

        Storage::append($fileName, 'Api name is :  ' . $headerdata['api_name'] . "\n");
        Storage::append($fileName, 'Api key is :  ' . $api . "\n");
        if (isset($headerdata['Auth-token'])) {
            Storage::append($fileName, ' Auth-token is :  ' . $headerdata['Auth-token'] . "\n");
        }
        Storage::append($fileName, 'Api-Url is :  ' . $headerdata['api_url'] . "\n");
        Storage::append($fileName, 'Master Affiliate name:   ' . $affilateName . "\n");
        Storage::append($fileName, 'Date-Time(According to Sydney time) : ' . $date . "\n");
        Storage::append($fileName, 'Service : ' . $service . "\n");
        Storage::append($fileName, 'IP Address : ' . $_SERVER['REMOTE_ADDR'] . "\n");

        $mailData = EmailTemplate::where("title", "api_error_notificaton")->where('status', 1)->first();

        if ($mailData) {
            $massage = $mailData->description;
            $mailArr['from_name'] = $mailData->from_name ?? 'CIMET Core';
            $mailArr['service_id'] = Lead::getService(true);
            $mailArr['subject'] = $mailData->subject;
            $mailArr['cc_mail'] = $mailData->cc_email ? [$mailData->cc_email] : [];
            $mailArr['bcc_mail'] = $mailData->bcc_email ? [$mailData->bcc_email] : [];
            $mailArr['html_msg']  = str_replace("@api-name@", $headerdata['api_name'], $massage);

            $attachMents = [];
            array_push($attachMents, (object) ['filename' => $fileName, 'content' => Storage::disk('local')->get($fileName)]);
            $mailArr['attachments'] = $attachMents;

            $mailArr['user_email']  =  explodeMultipleEmailAddress($mailData->to_email);
            $nodeMailer = new NodeMailer(true);
            if ($nodeMailer->sendMailBySmtp($mailArr)) {
                unlink(storage_path('app/' . $fileName));
                return ['success' => true, 'message' => 'mail sent succefully'];
            }
        }

        return ['success' => false, 'message' => 'Something went wrong'];
    }

    public function makeHeaders($msg, $method)
    {
        $request = app('request');
        $headerdata = [];
        $headerdata['Auth-token'] = $request->header('Auth-token');
        $headerdata['api_key'] = $request->header('api-key');
        $headerdata['api_name'] = $method;
        $headerdata['api_url'] = $request->url();
        $headerdata['status'] = false;
        $headerdata['error_message'] = $msg;
        return $headerdata;
    }
}
