<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Visitor\{Methods, Relationship, Redis, Validation};
use App\Traits\CommonApi\{BasicCrudMethods};

use App\Traits\Customer\{
  Methods as customerMethods,
  Validation  as customerValidation,
  Closure
};

/**
 * Visitor Model.
 * Author: Sandeep Bangarh
 */

class Visitor extends Model
{
  use Methods, Relationship, Redis, Validation, customerMethods, customerValidation, Closure, BasicCrudMethods;

  protected $fillable = ['title', 'first_name', 'middle_name', 'last_name', 'email', 'dob', 'phone', 'phone_status', 'alternate_phone', 'domain'];

  protected static $gdprFields = ['first_name', 'middle_name', 'last_name', 'email', 'phone', 'alternate_phone'];
}
