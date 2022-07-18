<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderSectionOption extends Model{
   
    protected $table='provider_section_options';
    protected $fillable = ['provider_section_id','section_option_status','section_option_id','min_value_limit','max_value_limit'];

    public function section_sub_options(){
        return $this->hasMany(ProviderSectionSubOption::class,'section_option_id','id')->select('section_sub_option_id','id','section_option_id','section_sub_option_status')->where('section_sub_option_status',1);
    }
}
