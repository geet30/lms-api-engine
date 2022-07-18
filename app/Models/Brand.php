<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\MobilePlan\{BasicCrudMethods};


class Brand extends Model
{
    use BasicCrudMethods;
    protected $table = 'brands';
    protected $fillable = ['title', 'status', 'created_at', 'updated_at', 'deleted_at', 'brand_unique_id', 'os_name'];
    const ONE = 1;
}
