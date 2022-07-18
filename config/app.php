<?php

return [
    'CUSTOM_LOGS' => env('CUSTOM_LOGS', true),
    'ENABLE_REDIS_CACHE' => env('ENABLE_REDIS_CACHE', true),
    'debug' => true, 
    'master_tab_manage_section'=>[1=>'personal_details',2=>'connection_details',3=>'identification_details',4=>'employment_details',5=>'connection_address',6=>'billing_and_delivery_address'],
    'personal_details'=>[1 => 'Name',2 => 'Email',3=>'Phone Number',4=>'Alternative Phone Number',5=>'Date of Birth'],

    'connection_details'=>[1 => 'Already have an account with the provider',2 => 'Keep existing phone number'],
   
    'identification_details'=>[1 =>'Drivers License',2 =>'Australian Passport',3=>'Medicare Card',4=>'Foreign Passport',5=>'Concession Card'],
    
    'employment_details'=>[1 =>'Industry',2 =>'Occupation type',3=>'Employment Status',4=>'Minimum time of employment',5=>'Do you have Credit card'],

    'connection_address'=>[1 =>'Minimum residential status',2 =>'Connection delivery date'],
    $billingAddress=['Billing Address'=>array(1=>'Email',2=>'Current Address',3=>'Other Address')],
    $deliveryAddress=['Delivery Address'=>array(1=>'Current Address',2=>'Other address')],
    'plan_static_days'=>365,
    $primaryIdentification = ['Primary Identification' => array(1 =>'Drivers License',2 =>'Australian Passport',3=>'Medicare Card',4=>'Foreign Passport',5=>'Concession Card')],
    $secondaryIdentification = ['Secondary Identification' => array(1 =>'Drivers License',2 =>'Australian Passport',3=>'Medicare Card',4=>'Foreign Passport',5=>'Concession Card')],

    'multi_identification_details'=>[1=>$primaryIdentification,2=>$secondaryIdentification],

   'billing_and_delivery_address'=>[1=>$billingAddress,2=>$deliveryAddress],
   'ABN_URL' => env('ABN_URL'),
   'ABN_ID' => env('ABN_ID'),
   'tokenx_login_url'=>env('TOKENX_URL').env('TOKENX_LOGIN_SLUG'),
   'tokenx_authkey_url'=>env('TOKENX_URL').env('TOKENX_AUTHKEY_SLUG'),
   'tokenx_detoken_url'=>env('TOKENX_URL').env('TOKENX_DETOKENIZED_SLUG'),
   'OTP_PWD' => env('OTP_PWD'),
   'JWT_TTL' => env('JWT_TTL'),
   'CACHE_SERVICES' => env('CACHE_SERVICES'),
   'key' => env('APP_KEY'),

   'cipher' => 'AES-256-CBC',

   'timezone' => 'Australia/Sydney',

   'ENABLE_FIREWALL' => env('ENABLE_FIREWALL', false),

   'ENABLE_RATE_LIMIT' => env('ENABLE_RATE_LIMIT', false)
];
