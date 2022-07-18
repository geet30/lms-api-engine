<?php

use Monolog\Logger;
use Jenssegers\Agent\Agent;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\DB;


if (!function_exists('encrypt_decrypt')) {

  function encrypt_decrypt($action, $string)
  {
    $output = false;
    $encryptMethod = config('encryption.encrypt_decrypt_method');
    $secretKey = config('encryption.encrypt_decrypt_secret_key');
    $secretIv = config('encryption.encrypt_decrypt_secret_iv');
    $key = hash('sha256', $secretKey);
    $sslIv = substr(hash('sha256', $secretIv), 0, 16);
    if ($action == 'encrypt') {
      $output = openssl_encrypt($string, $encryptMethod, $key, 0, $sslIv);
      $output = base64_encode($output);
    } else if ($action == 'decrypt') {
      $output = openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $sslIv);
    }
    return $output;
  }
}


if (!function_exists('saveLog')) {

  function saveLog($filePath, $message)
  {
    $canWriteLogs = config('app.CUSTOM_LOGS');
    if ($canWriteLogs == true) {
      $viewLog = new Logger('LOG');
      $viewLog->pushHandler(new StreamHandler($filePath, Logger::INFO));
      $viewLog->info('', $message);
    }
  }
}

if (!function_exists('encryptGdprData')) {

  function encryptGdprData($string)
  {
    if (!empty($string)) {
      $encryptMethod = config('encryption.encrypt_decrypt_method');
      $key = hash('sha256', config('encryption.gdpr_secret_key'));
      $secretIv = substr(hash('sha256', config('encryption.gdpr_secret_iv')), 0, 16);
      $string = strtoupper($string);
      return base64_encode(openssl_encrypt($string, $encryptMethod, $key, 0, $secretIv));
    }
    return $string;
  }
}

if (!function_exists('decryptGdprData')) {

  function decryptGdprData($output)
  {
    if (!empty($output)) {
      $encryptMethod = config('encryption.encrypt_decrypt_method');
      $key = hash('sha256', config('encryption.gdpr_secret_key'));
      $secretIv = substr(hash('sha256', config('encryption.gdpr_secret_iv')), 0, 16);
      $output = openssl_decrypt(base64_decode($output), $encryptMethod, $key, 0, $secretIv);
      $output = strtolower($output);
      if (!filter_var($output, FILTER_VALIDATE_EMAIL)) {
        $output = ucfirst($output);
      }
      return $output ?? '';
    }
    return $output;
  }
}
if (!function_exists('setTokenexEncryptData')) {

  function setTokenexEncryptData($string)
  {
    $encrypt_method = "AES-256-CBC";
    $key = hash('sha256', config('app.tokenex_secret_key'));
    $iv = substr(hash('sha256', config('app.tokenex_secret_iv')), 0, 16);
    return base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
  }
}
if (!function_exists('encryptBankDetail')) {

  function encryptBankDetail($string)
  {
    $encrypt_method = "AES-256-CBC";
    $key = hash('sha256', env('bank_secret_key'));
    $iv = substr(hash('sha256', env('bank_secret_iv')), 0, 16);
    return base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
  }
}
if (!function_exists('successResponse')) {

  function successResponse($msg, $code, $data = [])
  {
    return response()->json(['status' => true, 'code' => $code, 'message' => $msg, 'data' =>  $data], HTTP_STATUS_OK);
  }
}

if (!function_exists('errorResponse')) {

  function errorResponse($errors, $httpCode, $errorCode, $method = null)
  {
    $msg = $errors;
    if (is_array($errors) && count($errors)) {
      $msg = 'Please resolve all captured errors';
    }
    $httpCode = getHttpStatusCode($httpCode);
    
    if ($method && config('env.EXCEPTION_ENABLED')) {
      dispatch(new \App\Jobs\SendExceptionMail($msg, $method));
    }
    
    return response()->json(['status' => false, 'code' => $errorCode, 'message' => $msg, 'errors' => $errors], $httpCode);
  }
}

if (!function_exists('getHttpStatusCode')) {

  function getHttpStatusCode($code)
  {
    if ($code == 0 || is_string($code)) {
      return 400;
    }

    return $code;
  }
}
if (!function_exists('getUserAgent')) {
  function getUserAgent($user_agent)
  {

    if ($user_agent || $user_agent != '') {
      $agent = new Agent();
      $headers = $agent->setUserAgent($user_agent);
      $agent->setHttpHeaders($headers);
      $browser = $agent->browser();
      $version = $agent->version($browser);
      $platform = $agent->platform();

      if ($agent->isDesktop()) {
        $device = "Desktop";
      } elseif ($agent->isPhone()) {
        $device = "Phone";
      } else {
        $device = "Others";
      }
      return array(
        'user_agent' => $user_agent,
        'browser' => $browser,
        'version' => $version,
        'platform' => $platform,
        'device' => $device,
      );
    } else {
      return array(
        'user_agent' => 'N/A',
        'browser' => 'N/A',
        'version' => 'N/A',
        'platform' => 'N/A',
        'device' => 'N/A',
      );
    }
  }
}

if (!function_exists('loginTokenx')) {

  function loginTokenx($request, $senderId, $num, $message, $smsType)
  {
    $response = [];
    try {
      $url = config('url.sms_login');
      $header = [
        'Content-Type'     => 'application/json',
        'API-KEY'      => $request->header('api-key')
      ];
      $bodyData = [
        'service_id' => $request->header('serviceId')
      ];
      $client = new \GuzzleHttp\Client();
      $responses = $client->request('POST', $url, [
        'headers' => $header,
        'body' => json_encode($bodyData),
        'http_errors' => false
      ]);
      if ($responses->getStatusCode() == 200) {
        $data =  json_decode($responses->getBody());

        if ($smsType == 'plivo')
          $smsApiUrl = config('url.plivo_api_url');
        if ($smsType == 'twillio')
          $smsApiUrl = config('url.twilio_api_url');
        return sendSmsLambda($request, $senderId, $data->token, $smsApiUrl, $num, $message);
      } else {
        $http_status = 400;
        $response['message'] = "Token not found.";
        $response['status'] = $http_status;
      }
      return $response;
    } catch (\Exception $err) {
      $response['message'] = $err->getMessage();
      $response['status'] = 400;
      return $response;
    }
  }
}

if (!function_exists('sendSmsLambda')) {

  function sendSmsLambda($request, $senderId, $auth_token, $apiUrl, $contactNo, $message)
  {
    $bodyData = [
      'phonenumber' => $contactNo,
      'content' => $message,
      'senderid' => $senderId,
      'service_id' => $request->header('ServiceId')
    ];
    $header = [
      'Content-Type'     => 'application/json',
      'API-KEY'      => $request->header('api-key'),
      'authorization' => 'Bearer ' . $auth_token
    ];
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $apiUrl, [
      'headers' => $header,
      'body' => json_encode($bodyData),
      'http_errors' => false
    ]);
    if ($response->getStatusCode() == 200) {
      $data =  json_decode($response->getBody());
      $result = array('status' => 200, 'response' => $data);
    } else {
      $result['message'] = "Token not found.";
      $result['status'] = 400;
    }
    return $result;
  }
}

if (!function_exists('phoneNumber')) {
  function phoneNumber($phone)
  {
    $num = $phone;
    //check if phone number have start from 0 else it append 0 
    if (substr($phone, 0, 2) == 61) {
      $num = substr($phone, 2);
      if (substr($num, 0, 1) != 0)
        $num = '0' . $num;
    } elseif (substr($phone, 0, 1) != 0) {
      $num = '0' . $phone;
    }

    return $num;
  }
}

if (!function_exists('getProductModel')) {
  function getProductModel()
  {
    $serviceId = request()->header('serviceid')??1;
    if (request()->has('service_id')) $serviceId = request()->service_id;

    $model = '';
    switch ($serviceId) {
      case '1':
        $model = '\App\Models\SaleProductsEnergy';
        break;

      case '2':
        $model = '\App\Models\SaleProductsMobile';
        break;

      case '3':
        $model = '\App\Models\SaleProductsBroadband';
        break;

      default:
        $model = '\App\Models\SaleProductsEnergy';
        break;
    }

    return $model;
  }
}

if (!function_exists('tokenizedAuth')) {

  function tokenizedAuth($request)
  {

    $response = [];
    $url = config('app.tokenx_login_url');
    $header = [
      'Content-Type'     => 'application/json',
      'API-KEY'      => $request->header('api-key')
    ];
    $api_key = $request->header('api-key');
    $authUrl = config('app.tokenx_authkey_url');
    $bodyData = [
      'service_id' => $request->header('serviceId')
    ];


    $client = new \GuzzleHttp\Client();
    $responses = $client->request('POST', $url, [
      'headers' => $header,
      'body' => json_encode($bodyData),
      'http_errors' => false
    ]);

    $data =  json_decode($responses->getBody());

    if ($responses->getStatusCode() == 200) {
      $data =  json_decode($responses->getBody());
      $message = 'true';
      return  getLambdaToken($request, $data->token, $message, $api_key, $authUrl);
    } else {
      return false;
    }
    return $response;
  }
}
if (!function_exists('getLambdaToken')) {

  function getLambdaToken($request,  $auth_token,  $message, $api_key, $apiUrl)
  {
    $response = [];
    $bodyData = [
      'content' => $message,
      'service_id' => $request->header('ServiceId')
    ];
    $header = [
      'Content-Type'     => 'application/json',
      'API-KEY'      => $request->header('api-key'),
      'authorization' => 'Bearer ' . $auth_token
    ];
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $apiUrl, [
      'headers' => $header,
      'body' => json_encode($bodyData),
      'http_errors' => false
    ]);
    $data =  json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
      $data =  json_decode($response->getBody());
      return array('status' => 200, 'response' => $data);
    } else {
      return false;
    }
    return $response;
  }
}

if (!function_exists('savetokenizedAuth')) {

  function savetokenizedAuth($request,  $auth_token,  $message, $api_key, $apiUrl)
  {
    $response = [];
    $bodyData = [
      'content' => $message,
      'service_id' => $request->header('ServiceId')
    ];
    $header = [
      'Content-Type'     => 'application/json',
      'API-KEY'      => $request->header('api-key'),
      'authorization' => 'Bearer ' . $auth_token
    ];
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $apiUrl, [
      'headers' => $header,
      'body' => json_encode($bodyData),
      'http_errors' => false
    ]);
    $data =  json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
      $data =  json_decode($response->getBody());
      return array('status' => 200, 'response' => $data);
    } else {
      return false;
    }
    return $response;
  }
}
if (!function_exists('detokenizedAuth')) {

  function detokenizedAuth($request)
  {

    $response = [];
    $url = config('app.tokenx_login_url');
    $header = [
      'Content-Type'     => 'application/json',
      'API-KEY'      => $request->header('api-key')
    ];
    $api_key = $request->header('api-key');
    $authUrl = config('app.tokenx_detoken_url');
    $bodyData = [
      'service_id' => $request->header('serviceId')
    ];


    $client = new \GuzzleHttp\Client();
    $responses = $client->request('POST', $url, [
      'headers' => $header,
      'body' => json_encode($bodyData),
      'http_errors' => false
    ]);

    $data =  json_decode($responses->getBody());

    if ($responses->getStatusCode() == 200) {
      $data =  json_decode($responses->getBody());
      $message = 'true';
      return  getLambdaDeTokenAuth($request, $data->token, $message, $api_key, $authUrl);
    } else {
      return false;
    }
    return $response;
  }
}
if (!function_exists('getLambdaDeTokenAuth')) {

  function getLambdaDeTokenAuth($request,  $auth_token,  $message, $api_key, $apiUrl)
  {
    $response = [];
    $bodyData = [
      'token_value' => $request['token_value'],
      'content' => $message,
      'service_id' => $request->header('ServiceId')
    ];
    $header = [
      'Content-Type'     => 'application/json',
      'API-KEY'      => $request->header('api-key'),
      'authorization' => 'Bearer ' . $auth_token
    ];
    $client = new \GuzzleHttp\Client();
    $response = $client->request('POST', $apiUrl, [
      'headers' => $header,
      'body' => json_encode($bodyData),
      'http_errors' => false
    ]);
    $data =  json_decode($response->getBody());
    if ($response->getStatusCode() == 200) {
      $data =  json_decode($response->getBody());
      return array('status' => 200, 'response' => $data);
    } else {
      return false;
    }
    return $response;
  }
}

if (!function_exists('public_path')) {
  /**
   * Get the path to the public folder.
   *
   * @param  string $path
   * @return string
   */
  function public_path($path = '')
  {
    return env('PUBLIC_PATH', base_path('public')) . ($path ? '/' . $path : $path);
  }
}

if (!function_exists('resetTableIndex')) {
  /**
   * Get the path to the public folder.
   *
   * @param  string $table
   * @return boolean
   */
  function resetTableIndex($table)
  {
    if ($table) {
      DB::select("ALTER TABLE `$table` AUTO_INCREMENT = 1");
    }
    return true;
  }
}

/**
	 * Name: explodeMultipleEmailAddress()
	 * Purpose: Common function to split emails.
	 */
  if (!function_exists('explodeMultipleEmailAddress')) {
    function explodeMultipleEmailAddress($data)
    {
        if (strpos($data, ',') !== false) {
          $explodeRegularEmail = explode(',', $data);
          $mailAddress = [];
          foreach ($explodeRegularEmail as $explodeRegularEmails) {
            $mailAddress[] = $explodeRegularEmails;
          }
        } else {
          $mailAddress[] = $data;
        }
        return $mailAddress;
    }
  }
	
