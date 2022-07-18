<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Addons\
                                {
                                    BasicCrud, 
                                    Relationship,
                                };

class PlanAddonsMaster extends Model
{
    use HasFactory;
    use Relationship,BasicCrud;
    protected $table = 'plan_addons_master';

    public function cost_type(){
        return $this->hasOne('App\Models\CostType','id','cost_type_id')->where(function($q){
                $q->where('status', '=','1')
                ->where('is_deleted', '=','0')
                ->orderBy('order','DESC');
            }
            );
    }
}