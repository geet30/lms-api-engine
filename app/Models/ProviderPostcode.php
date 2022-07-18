<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\ProviderPostcode\{Accessor,BasicCrudMethods,Mutators,Relationship};

class ProviderPostcode extends Model
{
    use HasFactory;
    protected $fillable=['provider_id','distributor_id','postcode','state','suburb_id','type','status','is_deleted'];
}
