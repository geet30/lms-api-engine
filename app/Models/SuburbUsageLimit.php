<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{DmoVdoPrice
};

class SuburbUsageLimit extends Model
{
  protected $table = 'suburb_usage_limits';
  protected $fillable = ['state','elec_low_range','elec_medium_range','elec_high_range','gas_low_range','gas_medium_range','gas_high_range'];
	
}
