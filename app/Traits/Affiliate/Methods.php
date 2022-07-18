<?php

namespace App\Traits\Affiliate;

use App\Models\{AffiliateTemplates, AffiliateTemplateAttributes};
use DB;

trait Methods
{
	/**
	 * Get affiliate Referal Code.
	 * Author: Sandeep Bangarh
	 */
	static public function affiliateReferalCode($refralCode, $columns = '*')
	{
		return self::where('referal_code', $refralCode)->select($columns)->first();
	}
	static public function getAffiliateData($userID, $columns = '*',$apiKey=null)
	{
		
		return self::select($columns)->where('user_id', $userID)->with([
			'getthirdpartyapi' => function ($query)  {
				$query->select('user_id', 'subaccount_id', 'subaccount_key');
			},'getApiKeyData'=> function($q)use( $apiKey){
				$q->where('api_key',encryptGdprData($apiKey));
			},'affiliateParameter'
		])->first();
	}
	public static function getHtmlParameter($source, $types, $serviceType)
	{

		$request = [];
		$request['source_type'] = $source;
		$request['service_id'] = $serviceType;
		$request['template_type'] = $types;
		$remarketing = false;
		if (($source == AffiliateTemplates::TYPE_EMAIL && $types == AffiliateTemplates::SEND_PLAN && $serviceType == AffiliateTemplates::MOBILE_SERVICE) || ($source == AffiliateTemplates::TYPE_EMAIL && $types == AffiliateTemplates::SEND_PLAN && $serviceType == AffiliateTemplates::BROADBAND_SERVICE) || ($source == AffiliateTemplates::TYPE_EMAIL && $types == AffiliateTemplates::REMARKETING && $serviceType == AffiliateTemplates::MOBILE_SERVICE)) {
			$request['template_type'] = AffiliateTemplates::ONE;
		}

		if (($source == AffiliateTemplates::TYPE_EMAIL && $types == AffiliateTemplates::REMARKETING) && ($serviceType == AffiliateTemplates::ENERGY_SERVICE || AffiliateTemplates::BROADBAND_SERVICE)) {
			$remarketing = true;
		}
		$data = AffiliateTemplateAttributes::select('attribute')->where($request)->orWhere(function ($query) {
			$query->where(['service_id' =>  AffiliateTemplates::ZERO, 'source_type' =>  AffiliateTemplates::ZERO, 'template_type' =>  AffiliateTemplates::ZERO]);
		})->when($remarketing, function ($query) {
			$query->orWhere(function ($query) {
				$query->where(['service_id' =>  AffiliateTemplates::ZERO, 'source_type' =>  AffiliateTemplates::ONE, 'template_type' =>  AffiliateTemplates::FIVE]);
			});
		})->get()->toArray();
		return $data;
	}
}
