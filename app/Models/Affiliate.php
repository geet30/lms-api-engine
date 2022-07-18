<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Affiliate\{ Relationship, Methods };

/**
* Affiliate Model.
* Author: Sandeep Bangarh
*/

class Affiliate extends Model
{
    use Relationship, Methods;

    protected $fillable = ['user_id', 'parent_id', 'legal_name', 'company_name', 'sender_id', 'support_phone_number', 'lead_export_password', 'sale_export_password', 'generate_token', 'show_agent_portal', 'sub_affiliate_type', 'referral_code_url', 'referral_code_title', 'referal_code', 'logo', 'dedicated_page', 'facebook_url', 'twitter_url', 'instagram_url', 'youtube_url', 'linkedin_url', 'google_plus_url', 'lead_data_in_cookie', 'lead_ownership_days_interval', 'debit_info_password', 'allow_credit_score', 'default_credit_score', 'allow_default_credit_score', 'status'];

    public function getthirdpartyapi()
    {
        return $this->hasOne('App\Models\AffiliateThirdPartyApi', 'user_id', 'user_id')->where('third_party_platform', 1)->where('status', 1);
        
    }

    public function getApiKeyData(){
        return $this->hasOne('App\Models\AffiliateKeys', 'user_id', 'user_id');
    }
}
