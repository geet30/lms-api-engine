<?php

namespace App\Repositories\Address;


use App\Models\{PlanMobile,AffiliateKey,AffiliateProvider,Provider,ProviderList,
    Variant,PlanHandset,Handset,PlanVariant,ProviderLogo,PlanContract,InternalStorage,PlanContent,Color,ConnectionType
};
use DB;
use Storage;
use Illuminate\Validation\Rule;
use Session;

Class GetAddressDetail
{


    /**
     * @param array $input
     *
     * @throws GeneralException // @phpstan-ignore-line
     *
     * @return bool
     */
    public function searchAddressDetails($search_address = null){
       
        try{
            $dataArray=[];
            $request_key=config('url.data_tool_request_key');
            $url=config('url.gnaf_search_url');
            $XmlWriter = new \XMLWriter();
            $XmlWriter->openMemory();
            $XmlWriter->startDocument("1.0", "UTF-8");
            $XmlWriter->startElement('DtRequest');
            $XmlWriter->writeAttribute("Method", config('url.data_tool_method_search'));
            $XmlWriter->writeAttribute("AddressLine",$search_address);
            $XmlWriter->writeAttribute("ResultLimit", "");
            $XmlWriter->writeAttribute("RequestId","");
            $XmlWriter->writeAttribute("RequestKey",$request_key);
            $XmlWriter->writeAttribute("DepartmentCode", "");
            $XmlWriter->writeAttribute("OutputFormat", "JSON");
            $XmlWriter->endElement();
            $XmlWriter->endDocument();
            $XmlString = $XmlWriter->outputMemory();
            $ServerPath = new \SoapClient($url);
            $KleberRequest = $ServerPath->ProcessXmlRequest(array('DtXmlRequest' => $XmlString));
            $KleberResultResponse = $KleberRequest->ProcessXmlRequestResult;
            $dataArray=json_decode($KleberResultResponse);
            // if(isset($dataArray->DtResponse->ErrorMessage) && $dataArray->DtResponse->ErrorMessage!=''){
            // 	self::sendApiCrashReport('search_address', $dataArray->DtResponse->ErrorMessage);
            // }
            return $dataArray->DtResponse->Result;

        }catch(\Exception $e){
            return ['status' =>false ,'message'=>'Something went wrong. Please try again Later.'.$e->getMessage(),'status_code'=>400];
        }
    }

    public function retrieveAddressData($record_id=null){
		$dataArray=[];
		$request_key=config('url.data_tool_request_key');
		$url=config('url.gnaf_retrieve_url');
		$XmlWriter = new \XMLWriter();
		$XmlWriter->openMemory();
		$XmlWriter->startDocument("1.0", "UTF-8");
		$XmlWriter->startElement('DtRequest');
		$XmlWriter->writeAttribute("Method", config('url.data_tool_method_retrieve'));
		$XmlWriter->writeAttribute("RecordId",$record_id);
		$XmlWriter->writeAttribute("RequestId","");
		$XmlWriter->writeAttribute("RequestKey",$request_key);
		$XmlWriter->writeAttribute("DepartmentCode", "");
		$XmlWriter->writeAttribute("OutputFormat", "JSON");
		$XmlWriter->endElement();
		$XmlWriter->endDocument();
		$XmlString = $XmlWriter->outputMemory();
		$ServerPath = new \SoapClient($url);
		$KleberRequest = $ServerPath->ProcessXmlRequest(array('DtXmlRequest' => $XmlString));
		$KleberResultResponse = $KleberRequest->ProcessXmlRequestResult;
		$dataArray=json_decode($KleberResultResponse);
		// if(isset($dataArray->DtResponse->ErrorMessage) && $dataArray->DtResponse->ErrorMessage!=''){
		// 	self::sendApiCrashReport('retrive_address', $dataArray->DtResponse->ErrorMessage);
		// }
		return $dataArray->DtResponse->Result;
	}
}
