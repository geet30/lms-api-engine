<?php

namespace App\Traits\AffiliateTemplate;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Lead;

trait Methods
{
	/**
	 * Get affiliate Referal Code.
	 * Author: Sandeep Bangarh
	 */
	static public function sendEmailAndSms($products, $visitor, $referenceNos)
	{
		$user = Auth::user();
		$firstRow = $products->first();
		$service = Lead::getService(true);
		$tempObj = null;

		$emailTemplatesColumns = ['id', 'template_id', 'type', 'email_type', 'target_type', 'immediate_sms','subject', 'from_name', 'from_email', 'email_cc', 'email_bcc', 'sending_domain', 'ip_pool','content','source_type','sender_id_method','sender_id','template_name'];
		if ($service == 1) {
			$elecData = $products->where('product_type', 1)->first();
			$gasData = $products->where('product_type', 2)->first();
			$elecProvider = $elecData ? $elecData['provider_id'] : '';
			$gasProvider = $gasData ? $gasData['provider_id'] : '';
			
			if ($elecData && $gasData) {
				if ($elecProvider == $gasProvider) {
					/** if both plan applied but providers are different **/
					$tempObj = static::getData(['email_type' => 1, 'template_type' => 4, 'user_id' => $user->id, 'status' => 1,'service_id' => 1], $emailTemplatesColumns);
				} elseif ($elecProvider != $gasProvider) {
					/** if both plan applied and providers are same **/
					$tempObj = static::getData(['email_type' => 1, 'template_type' => 3, 'user_id' => $user->id, 'status' => 1,'service_id' => 1], $emailTemplatesColumns);
				}
			} elseif ($elecData && !$gasData) {
				/** if only electricity plan is applied **/
				$tempObj = static::getData(['email_type'  => 1, 'template_type' => 1, 'user_id' => $user->id, 'status' => 1,'service_id' => 1], $emailTemplatesColumns);
			} elseif (!$elecData && $gasData) {
				/** if only gas plan is applied **/
				$tempObj = static::getData(['email_type'  => 1, 'template_type' => 2, 'user_id' => $user->id, 'status' => 1,'service_id' => 1], $emailTemplatesColumns);
			}
		}
		
		if ($service != 1) {
			$templateType =  0;
			if ($firstRow['product_type'] == 1) {
				$templateType =  5;
			}

			if ($firstRow['product_type'] == 2) {
				$templateType =  6;
			}
			
			$tempObj = static::getData(['email_type' => 1, 'user_id' => $user->id, 'status' => 1,'service_id' => $service, 'template_type' => $templateType], $emailTemplatesColumns);
		}
		
		$affiliate = $attributes = null;
		
		if ($tempObj && !$tempObj->isEmpty()) {
			$firstTempObj = $tempObj->where('type', 1)->first();
			if ($firstTempObj) {
				$templateId = $firstTempObj->template_id;
			}
			$affiliate = $user->getAffiliate(['abn', 'parent_id', 'legal_name', 'support_phone_number', 'youtube_url', 'twitter_url', 'facebook_url', 'linkedin_url', 'google_url', 'subaccount_id', 'page_url', 'address', 'dedicated_page','rc_code','referal_code'], true, true, true, true);
			$attributes = DB::table('affiliate_template_attribute')->where('service_id', $service)->get();
			dispatch(new \App\Jobs\SendWelcomeMail($user, $products, $visitor, $affiliate, $attributes, $firstTempObj, $referenceNos));
			
			/** Send welcome and confirmation sms just after sale is created **/
			if ($tempObj) {
				$smsObj = $tempObj->where('type', 2)->whereIn('email_type', [1,3]);
				if (!$smsObj->isEmpty()) {
					foreach ($smsObj as $template) {
						if ($template->email_type == 3 && $template->immediate_sms == 1) {
							dispatch(new \App\Jobs\SendWelcomeSms($user, $products, $visitor, $affiliate, $attributes, $template));
						} elseif ($template->email_type == 1) {
							dispatch(new \App\Jobs\SendWelcomeSms($user, $products, $visitor, $affiliate, $attributes, $template));
						}
					}
				}
			}
		}
		
		return $tempObj;
	}

	static function getData($conditions, $columns = '*')
	{
		return DB::table('affiliate_templates')->select($columns)->where($conditions)->get();
	}
}
