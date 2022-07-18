<?php

namespace App\Traits\Lead;

// use App\Models\ApiResponse;
use GuzzleHttp\Client;

/**
* Lead Dialler model.
* Author: Sandeep Bangarh
*/

trait Dialler
{
    static function sendDataToDialler ($reference_no, $sale_id, $dialler_api_url, $username, $password)
    {
        try {
            /** Get affiliate or sub-affiliate name **/
            $company_name = auth()->user()->first_name;
            /** Create URL for CURL Request **/
            $url = $dialler_api_url . '?source=' . str_replace(' ', '', $company_name) . '&user=' . $username . '&pass=' . $password . '&function=update_lead&lead_id=' . $reference_no . '&status=100';
            $client = new Client();
            $res = $client->get($url);
            $res->getBody();
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ORDER_ERROR_CODE, __FUNCTION__);
        }
    }
}