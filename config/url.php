<?php
return [
    'nbh_login' => env('NBH_URL').env('NBH_LOGIN_SLUG') ,
    'nbh_address' => env('NBH_URL').env('NBH_ADDRESS_SLUG'),
    'sms_login' => env('SMS_URL').env('SEND_SMS_LOGIN_SLUG'),
    'plivo_api_url' => env('SMS_URL').env('SEND_SMS_PLIVO_SLUG'),
    'twilio_api_url' => env('SMS_URL').env('SEND_SMS_TWILIO_SLUG'),
    'data_tool_request_key' => env('DATA_TOOL_REQUEST_KEY'),
    'gnaf_search_url' => env('GNAF_SEARCH_URL'),
    'gnaf_retrieve_url' => env('GNAF_RETRIEVE_URL'),
    'data_tool_method_search' => env('DATA_TOOL_METHOD_SEARCH'),
    'data_tool_method_retrieve' => env('DATA_TOOL_METHOD_RETRIEVE')
];
