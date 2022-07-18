<?php

namespace App\Repositories\SparkPost;

use GuzzleHttp\Client;

class NodeMailer
{
	/**
	 * @var mixed
	 */
	private $baseUrl;

	/**
	 * @var mixed
	 */
	private $tokenUrl;

	/**
	 * @var array
	 */
	private $loginHeaders = [];

	/**
	 * @var \GuzzleHttp\Client
	 */
	private $client;

	/**
	 * @var mixed
	 */
	private $key;

	/**
	 * @var string
	 */
	private $contentType;

	/**
	 * @var mixed
	 */
	private $token;

	/**
	 * @var mixed
	 */
	private $smtpUrl;

	/**
	 * @var mixed
	 */
	private $smtpKey;

	/**
	 * @var mixed
	 */
	private $isSmtp;

	/**
	 * @param mixed $isSmtp
	*/
	public function __construct($isSmtp=null)
	{
		$this->isSmtp = $isSmtp;
		$this->baseUrl = config('env.SPARKPOST_URL');
		$this->tokenUrl = config('env.REBRANDLY_URL');
		
		$this->smtpUrl = config('env.NODE_MAILER_URL');
        $this->smtpKey = config('env.NODE_MAILER_KEY');

		$this->key = config('env.SPARKPOST_API_KEY');
		$this->contentType = 'application/json';
		$this->loginHeaders = [
			'Header'  => 'Content-type',
			'Content-Type' => $this->contentType,
			'API-KEY' => $isSmtp?$this->smtpKey:$this->key
		];
		$this->token = null;
		$this->client = new Client();
	}

	/**
	 * Get Token
	 * @return mixed
	 */
	public function setToken()
	{
		try {
			$endPoint = "/v1/login";
			$response = $this->client->post(
				($this->isSmtp?$this->smtpUrl:$this->baseUrl) . $endPoint,
				[
					'headers' => $this->loginHeaders,
					'body' => json_encode(['service_id' => 1])
				]
			);
			if ($response->getStatusCode() == 200) {
				$returnData =  json_decode($response->getBody());
				$this->token = $returnData->token;
			}


			return $this->token;
		} catch (\Exception $err) {
			
			return false;
		}
	}

	/**
	 * Send email
	 * @param array $sendRequest
	 * @param mixed $orgRes
	 * @return mixed
	 */
	public function sendMail($sendRequest, $orgRes = null)
	{
		try {
			$isOk = $this->setToken();
			if (!$isOk) return null;

			$endPoint = "/v1/mail/sendmail";
			$response = $this->client->post(
				$this->baseUrl . $endPoint,
				[
					'headers' => [
						'authorization' => 'Bearer ' . $this->token,
						'API-KEY' => $this->key,
						'Content-Type' => $this->contentType
					],
					'body' => json_encode($sendRequest)
				]
			);
			if ($orgRes) return $response;
			
			if ($response->getStatusCode() == 200) {
				return json_decode($response->getBody());
			}

			return null;
		} catch (\Exception $err) {
			return $err->getMessage().'  Line no:'. $err->getLine().'  File:'. $err->getFile();
		}
	}

	/**
	 * Send email via SMTP
	 * @param array $sendRequest
	 * @return mixed
	 */
	public function sendMailBySmtp($sendRequest) {
		try{
			$isOk = $this->setToken();
			if (!$isOk) return null;
			
			$endPoint="/v1/sendemail";
			$response=$this->client->post($this->smtpUrl.$endPoint,
				[
				'headers'=>[
					'authorization' => 'Bearer '.$this->token,
					'API-KEY' => $this->smtpKey,
					'Content-Type' => $this->contentType
				],	  
				'body'=>json_encode($sendRequest)
				]
			);
			if($response->getStatusCode()==200)
			{
				$returnData =  json_decode($response->getBody());
				return $returnData->data->messageId;
			}

			return null;
		}catch(\Exception $err)
		{	
			return null;
		}
	}

	/**
	 * Send email
	 * @param array $sendRequest
	 * @param mixed $orgRes
	 * @return mixed
	 */
	public function sendMailWithTemplate($sendRequest, $orgRes = null)
	{
		try {
			$isOk = $this->setToken();
			if (!$isOk) return null;
			$endPoint = "/v1/mail/sendmail_templateId";
			$response = $this->client->post(
				$this->baseUrl . $endPoint,
				[
					'headers' => [
						'authorization' => 'Bearer ' . $this->token,
						'API-KEY' => $this->key,
						'Content-Type' => $this->contentType
					],
					'body' => json_encode($sendRequest)
				]
			);
			
			if ($orgRes) return $response;
			
			if ($response->getStatusCode() == 200) {
				return json_decode($response->getBody());
			}

			return null;
		} catch (\Exception $err) {
			return false;
		}
	}

	/**
	 * Convert URL into rabrandly URL
	 * @param string $url
	 * @param string $domainName
	 * @return mixed
	*/
	public function rebrandlyUrl($url, $domainName = 'rebrand.ly')
	{
		try {
			$isOk = $this->setToken();
			if (!$isOk) return null;
			
			$endPoint = "/v1/short_url";
			$response = $this->client->post(
				$this->tokenUrl . $endPoint,
				[
					'headers' => [
						'authorization' => 'Bearer ' . $this->token,
						'API-KEY' => $this->key,
						'Content-Type' => $this->contentType
					],
					'body' => json_encode(['url'=>$url,'domain_name'=>$domainName])
				]
			);
			
			if ($response->getStatusCode() == 200) {
				$body = json_decode($response->getBody());
				return $body->token->shortUrl;
			}

			return null;
		} catch (\Exception $err) {
			return false;
		}
	}

	/**
	 * Convert URL into bitley URL
	 * @param string $url
	 * @return mixed
	*/
	public function bitlyUrlApi ($url) {
		/** Work to do **/
		return $url;
	}
}
