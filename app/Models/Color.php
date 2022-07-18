<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Color extends Model
{
    protected $fillable = ['title','hexacode','description','status','color_unique_id'];

    
    public function generateTags(): array
    {
        return [
            "Color"
        ];
    }


  
}
