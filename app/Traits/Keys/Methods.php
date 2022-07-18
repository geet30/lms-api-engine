<?php

namespace App\Traits\Keys;

use Illuminate\Support\Facades\DB;
use App\Models\User;

/**
 * Keys Methods model.
 * Author: Sandeep Bangarh
 */

trait Methods
{
  /**
   * Get Frame data with User.
   * Author: Sandeep Bangarh
   */
  static function getDataWithUser($apiKey, $columns = ['users.*'])
  {
    return DB::table('affiliate_keys')
      ->join('users', 'affiliate_keys.user_id', '=', 'users.id')
      ->join('affiliates', 'affiliate_keys.user_id', '=', 'affiliates.user_id')
      ->where(function ($q) use ($apiKey) {
        $q->where('affiliate_keys.api_key', encryptGdprData($apiKey))->orWhere('rc_code', $apiKey);
      })
      ->where('affiliate_keys.status', 1)
      ->where('affiliates.status', 1)
      ->where('users.status', 1)
      ->select($columns)
      ->first();
  }

  /**
   * Validate Affiliate or Subaffiliate.
   * Author: Sandeep Bangarh
   */
  static function validateAffiliate($apiKey)
  {

    $keyData = self::getDataWithUser($apiKey, ['users.id']);

    if (!$keyData) {
      return false;
    }

    return User::select('id')->find($keyData->id);
  }

  /**
   * Check if affiliate key exist.
   * Author: Sandeep Bangarh
   */
  static function isKeyExist($userId, $apiKey)
  {
    return DB::table('affiliate_keys')->where('user_id',  $userId)->where('api_key', encryptGdprData($apiKey))->exists();
  }

  /**
   * Get Primary ID.
   * Author: Sandeep Bangarh
   */
  static function getId($apiKey)
  {
    $apiData = DB::table('affiliate_keys')->where('api_key', encryptGdprData($apiKey))->select('id')->first();
    return $apiData ? $apiData->id : null;
  }

  /**
   * Get API Key data.
   * Author: Sandeep Bangarh
   */
  static public function frameReferalCode($refralCode)
  {
    return self::where('rc_code', $refralCode)->first();
  }
}
