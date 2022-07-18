<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{SuburbUsageLimit
};

class PostcodeLimit extends Model
{
    use HasFactory;
    protected $table = 'postcode_limits';
    protected $fillable = ['usage_type','suburb_usage_limit_id','post_code'];

    public function usageLimit(){
		return $this->belongsTo(SuburbUsageLimit::class,'suburb_usage_limit_id');
	}
	
}
