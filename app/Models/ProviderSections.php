<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderSections extends Model{
    
    protected $table='provider_sections';
    protected $fillable = ['section','section_status','provider_id','service_id'];
	
	public function section_options(){
		 return $this->hasMany(ProviderSectionOption::class,'provider_section_id','id')->where('section_option_status',1);
     }
    
}
