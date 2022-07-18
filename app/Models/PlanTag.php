<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\{Tag
};

class PlanTag extends Model
{
    use HasFactory;
    protected $table ='plan_tags';
    protected $fillable = ['plan_id','tag_id','is_deleted'];

    public function tags(){
		return $this->belongsTo(Tag::class,'tag_id','id');
	}
}
