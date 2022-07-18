<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Affiliate\{Methods};


class AffiliateTemplates extends Model
{
    use Methods;

    protected $table = 'affiliate_templates';
    protected $fillable = ['user_id', 'type', 'email_type', 'service_id', 'template_name', 'utm_name', 'utm_rm', 'utm_rm_date_status', 'rm_source', 'from_name', 'from_email', 'sending_domain', 'ip_pool', 'reply_to', 'subject', 'description', 'select_remarketing_type', 'remarketing_time', 'move_in_template', 'interval', 'remarketing_duplicate_check', 'opens_tracking', 'click_tracking', 'transactional', 'email_cc', 'email_bcc', 'content', 'status', 'source_type', 'branding_url', 'sender_id', 'sender_id_method', 'plivo_number', 'check_restricted_time', 'delay_time', 'template_id', 'template_set_id', 'target_type', 'immediate_sms'];
    const TYPE_SMS = 2;
    const TYPE_EMAIL = 1;
    const ZERO = '0';
    const ONE = '1';
    const TWO = 2;
    const THREE = 3;
    const DEFAULT_TIME = 5;
    const MINUTES = 60;
    const WELCOME = 1;
    const REMARKETING = 2;
    const CONFIRMATION = 3;
    const SEND_PLAN = 4;
    const ENERGY_SERVICE = 1;
    const MOBILE_SERVICE = 2;
    const BROADBAND_SERVICE = 3;
    const FIVE = 5;
    static function getAffTemplate($tempateType, $serviceId, $userId)
    {
        $template = self::where('type', 1)
            ->where('email_type', 4)
            ->where('user_id', $userId)
            ->where('service_id', $serviceId);
            if(isset($tempateType)&& !empty($tempateType)){
                $template= $template->where('template_type', $tempateType);
            }
            $template= $template->where('status', 1);
            $template= $template->first();
            
        return $template;
    }
}
