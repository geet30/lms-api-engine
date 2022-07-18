<?php
namespace App\Repositories\Abn;
class AbnRepository extends \SoapClient{

    private $guid; 

	public function __construct()
    {
		ini_set('error_reporting', (string) E_STRICT);
		$this->guid = config('app.ABN_ID');
		$params = array(
			'soap_version' => SOAP_1_1,
			'exceptions' => true,
			'trace' => 1,
			'cache_wsdl' => WSDL_CACHE_NONE
		); 

		parent::__construct(config('app.ABN_URL'), $params);
    }
    
    
	/**
	*Name:searchByAbn
	*Purpose: Function to hit the server and get responce for abn number 
	*/ 
	public function searchByAbn($abn){
		$params = new \stdClass();
		$params->searchString=$abn;
		$params->includeHistoricalDetails='N';
		$params->authenticationGuid= $this->guid;
		$result=$this->ABRSearchByABN($params);
		// filter result i.e. check is there any error or not.
		self::checkException($result, 'number');
		return $result;
	}

	/**
	*Name:searchByAbnName
	*Purpose: Function to hit the server and get responce fir abn name 
	*/
	public function searchByAbnName($abn){
		$params = new \stdClass();
		$params->externalNameSearch = new \stdClass(); 
		$params->externalNameSearch->name=$abn;
		$params->externalNameSearch->minimumScore=0;
		$params->authenticationGuid=$this->guid;
		$result=$this->ABRSearchByName($params);
		// filter result i.e. check is there any error or not.
		self::checkException($result, 'name');
		return $result;
	}



	 
	/**
	*Name:Abnbyname
	*Purpose: Function to get list of Business according to business name
	*Created By: Harpartap Singh
	*Created On :- 29 july 2017
	*/ 
	public function Abnbyname($abnNumber=null)
	{
		$result = $this->searchByAbnName($abnNumber); 

		if(array_key_exists('exception',(array) $result->ABRPayloadSearchResults->response))
		{
			return false;
		}

		if(!empty($result->ABRPayloadSearchResults->response->searchResultsList->searchResultsRecord)) {
			return $result->ABRPayloadSearchResults->response->searchResultsList->searchResultsRecord;
		}

		return	false;
	}	 
	  
	  
	/**
	*Name:Abnbyname
	*Purpose: Function to get list of Business according to business name
	*Created By: Harpartap Singh
	* Created On :- 29 july 2017
	*/ 
	public function Abnbynumber($abnName)
	{
		$result = $this->searchByAbn($abnName); 

		if(array_key_exists('exception',(array) $result->ABRPayloadSearchResults->response))
		{
			return false;
		}
		
		if(!empty($result->ABRPayloadSearchResults->response->businessEntity)) {
			return $result->ABRPayloadSearchResults->response->businessEntity;
		}

		return false;
	}	

	/*
	 * Name: checkException()
	 * Purpose: check if abn or acn api have negative exception or not. If yes then we will trigger email to admin. In case of postive exception we dont need to sent email.
	*/
	public function checkException($result, $api_type){
		$mail_content=[];
		$subject='';
		$title='Business Screen API Crash Alert';
		// check if api type is abn name then send content and subject according to that.
		if($api_type=='name'){
			$mail_content['content']='There is a crash reported on Company Name search API.';
			$subject='Company name search API crash alert';
		}
		// check if api type is abn number then send content and subject according to that.
		if($api_type=='number'){
			$mail_content['content']='There is a crash reported on ABN/ACN API.';
			$subject='ABN/ACN search API crash alert';
		}
		// if not empty resullt.
		if(!empty($result)){
			
			// check is there any exception key is present or not.if yes only then we will proceed further else not.
			if(array_key_exists('exception',(array) $result->ABRPayloadSearchResults->response)){
				// check exception code is present or not.
				if(array_key_exists('exceptionCode',(array) $result->ABRPayloadSearchResults->response->exception)){
					// check is exception code is webservices or not.
					if($result->ABRPayloadSearchResults->response->exception->exceptionCode=="WEBSERVICES" && (isset($result->ABRPayloadSearchResults->response->exception->exceptionDescription) && $result->ABRPayloadSearchResults->response->exception->exceptionDescription!="Search text is not a valid ABN or ACN")){
						// send crash report.
					}
				}
			}
		}
	}
}

?>
