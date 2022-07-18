<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Contract extends Model
{
    protected $table = 'contract';

    protected $fillable = ['id', 'contract_name', 'validity', 'description', 'status', 'created_at', 'updated_at', 'deleted_at'];

    const ONE = 1;
}
