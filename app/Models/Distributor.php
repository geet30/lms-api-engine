<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DistributorPostCode;
use App\Traits\Distributor\ { Methods, Relationship, Validation };

class Distributor extends Model
{
    use Methods, Relationship, Validation;
    
    protected $fillable = ['name', 'energy_type', 'status', 'is_deleted'];

    public function distrbutorPostcode()
    {
        return $this->hasMany(DistributorPostCode::class);
    }

    static function getDistributorCommon($id,$select){

       return self::where('id',$id)->select($select)->get();
    }
}
