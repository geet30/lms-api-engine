<?php
return [
    'Public_BUCKET_ORIGIN'      =>  env('BUCKET_ORIGIN_PUB'),
    'PRIVATE_BUCKET_ORIGIN'     =>  env('BUCKET_ORIGIN_PRI'),
    'DEV_FOLDER'                =>  env('DEV_FOLDER'),
    'AWS_BUCKET'                => 'mobile-compare',
    'DEFAULT_REGION'            => 'ap-southeast-2',
    'AFFILIATE_LOGO'            => 'affiliate/<aff-id>/logo/',
    'PROVIDER_LOGO'             => 'provider/<pro-id>/logo/',
    'HANDSET_LOGO'              => "handsets/<handset_id>/logo/",
    'HANDSET_MORE_INFO'         => "handsets/<handset_id>/more-info/<handset_info_id>/",
    'SPARKPOST_API_KEY'         => env('SPARKPOST_API_KEY'),
    'SPARKPOST_URL'             => env('SPARKPOST_URL'),
    'REBRANDLY_URL'                 => env('REBRANDLY_URL'),
    'NODE_MAILER_URL'           => env('NODE_MAILER_URL'),
    'NODE_MAILER_KEY'           => env('NODE_MAILER_KEY'),
    'EXCEPTION_ENABLED'         => env('EXCEPTION_ENABLED'),
    'ENABLE_REDIS' => env('ENABLE_REDIS')
];
