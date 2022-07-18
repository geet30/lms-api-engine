<?php

namespace App\Repositories\SparkPost;


class SparkPost
{
	public $baseUrl;
	public $loginheaders = [];
	public $client;
	public $affiliateKey;
	public $key;
	public $contentType;
	public $validation = [];

	public function __construct($request)
	{
		$this->baseUrl = config('env.SPARKPOST_URL');
		$this->affiliateKey = $request->header('API-Key');
		$this->contentType = 'application/json';
		$this->loginheaders = [
			'Header'  => 'Content-type',
			'Content-Type' => $this->contentType,
			'API-KEY' => $this->affiliateKey
		];

		$this->client = new \GuzzleHttp\Client();
	}


	/**
	 * Get Token
	 */
	public function getToken($data)
	{
		try {
			$api_url = "/v1/login";
			$response = $this->client->post(
				$this->baseUrl . $api_url,
				[
					'headers' => $this->loginheaders,
					'body' => json_encode($data)
				]
			);
			$returnData = [];
			if ($response->getStatusCode() == 200) {
				$returnData =  json_decode($response->getBody());
				$messgae = "token created successfully";
				$status = 200;
			} else {
				$status = 404;
				$messgae = "token not created successfully";
			}


			return ['status' => $status, 'message' => $messgae, 'data' => $returnData];
		} catch (\Exception $err) {
			$status = 400;
			return ['status' => $status, 'message' => $err->getMessage()];
		}
	}
	/**
	 * Send Password email
	 */
	public function sendMailTemplateId($sendrequest, $token)
	{
		try {
			
			$api_url = "/v1/mail/sendmail_templateId";
			$response = $this->client->post(
				$this->baseUrl . $api_url,
				[
					'headers' => [
						'authorization' => 'Bearer ' . $token,
						'API-KEY' => $this->affiliateKey,
						'Content-Type' => $this->contentType
					],
					'body' => json_encode($sendrequest)
				]
			);
			
			$status = 404;
			$messgae = "error while sending email";

			$returnData = [];
			if ($response->getStatusCode() == 200) {
				$returnData =  json_decode($response->getBody());

				if ($returnData->data->results->total_accepted_recipients == 1) {
					$messgae = "email Sent successfully";
					$status = 200;
				}
				$response = ['status' => true, 'message' => $messgae,'data'=>$returnData, 'status_code' => PLANLIST_SUCCESS_CODE];
			}
		} catch (\Exception $err) {
			$status = 400;
			return ['status' => $status, 'message' => $err->getMessage()];
		}
	}
}
