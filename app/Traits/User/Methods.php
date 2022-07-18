<?php

namespace App\Traits\User;
use Illuminate\Support\Facades\DB;

/**
* User Methods model.
* Author: Sandeep Bangarh
*/

trait Methods
{
    /**
     * Get settings.
     * Author: Sandeep Bangarh
     * @param array|string $conditions $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
    */
    function getAffiliate ($columns = '*', $withBank=null, $withKey=null, $withThirdParty=null, $withAddress=null) {
        $query = DB::table('affiliates')->select($columns)->where('affiliates.user_id', $this->id);

        if ($withKey) { 
			$query = $query->join('affiliate_keys', 'affiliates.user_id', '=', 'affiliate_keys.user_id');
		}
        
        if ($withBank) { 
			$query = $query->leftjoin('user_bank_details', 'affiliates.user_id', '=', 'user_bank_details.user_id');
		}

        if ($withThirdParty) { 
			$query = $query->leftjoin('affiliate_third_party_apis', 'affiliates.user_id', '=', 'affiliate_third_party_apis.user_id');
		}

        if ($withAddress) { 
			$query = $query->leftjoin('user_address', 'affiliates.user_id', '=', 'user_address.user_id');
		}

        
        return $query->first();
    }
}