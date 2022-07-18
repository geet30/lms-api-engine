<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use OwenIt\Auditing\Contracts\Auditable;

class Capacity extends Model
{
    protected $fillable = ['value','unit','description','status','created_at','updated_at','deleted_at','capacity_unique_id'] ;


    
    public function generateTags(): array
    {
        return [
            "Capacity"
        ];
    }

    // public function getIdAttribute($value)
    // {
    //     return set_encrypt_data($value);
    // }



    protected $appends = ['capacity_name'];
    
    public function getCapacityNameAttribute() {

        $value =$this->value; // @phpstan-ignore-line

        switch ($this->unit)
         {
            case '0':
                $value =$value.' MB';
                break;

            case '1':
                $value =$value.' GB';
                break;

            case '2':
                $value =$value.' TB';
                break;
            
            default:
                $value ='';
                break;
        }

        return  $value;
    }
}
