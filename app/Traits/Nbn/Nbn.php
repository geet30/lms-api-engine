<?php

namespace App\Traits\Nbn;

trait Nbn
{
    /**
     * Date: (11-March-2022)
     * set guzzle header data
     */
    private static function getAction($method, $bodyData, $url, $header)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request($method, $url, [
                'headers' => $header,
                'body' => json_encode($bodyData),
                'http_errors' => false
            ]);
            $returnData = [];
            if ($response->getStatusCode() == 200) {
                $returnData =  json_decode($response->getBody());
                $status = 200;
            } else {
                $returnData =  json_decode($response->getBody());
                $status = 404;
            }
            return ['status' => $status, 'data' => $returnData];
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
                'status' => 404
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    /**
     * Date: (11-March-2022)
     * login 
     */
    static function login($request)
    {
        try {
            $url = config('url.nbh_login');
            $sendData = [
                "service_id" => $request->header('serviceId'),
            ];
            $header = [
                'Content-Type'     => 'application/json',
                'API-KEY'      => $request->header('api-key')
            ];
            return self::getAction('post', $sendData, $url, $header);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    /**
     * Date: (11-March-2022)
     * set guzzle header data
     */
    static function getNbhAddress($request)
    {
        try {
            $url = config('url.nbh_address');
            $sendData = [
                "service_id" => $request->header('serviceId'),
                'address' => $request->address
            ];
            $header = [
                'Content-Type'     => 'application/json',
                'API-KEY'      => $request->header('api-key'),
                'authorization' => 'Bearer ' . $request->token
            ];;
            return self::getAction('post', $sendData, $url, $header);
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
}
