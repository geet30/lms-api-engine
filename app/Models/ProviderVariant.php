<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderVariant extends Model
{
   protected $table = 'provider_variants';

   protected $fillable = ['provider_id', 'handset_id', 'variant_id', 'type', 'vha_code', 'status'];

   public function handset()
   {

      return $this->hasOne('App\Models\Handset', 'id', 'handset_id');
   }
   public function provider()
   {

      return $this->hasOne('App\Models\Provider', 'id', 'provider_id');
   }
   public function variant()
   {
      return $this->hasOne('App\Models\Variant', 'id', 'variant_id');
   }
}
