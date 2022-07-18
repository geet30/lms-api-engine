<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class InternalStorage extends Model
{
    protected $fillable = ['value', 'unit', 'description', 'status', 'created_at', 'updated_at', 'deleted_at', 'storage_unique_id'];



    public function generateTags(): array
    {
        return [
            "InternalStorage"
        ];
    }
    protected $appends = ['storage_name'];

    public function getStorageNameAttribute()
    {

        $value = $this->value; // @phpstan-ignore-line

        switch ($this->unit) // @phpstan-ignore-line
        {
            case '0':
                $value = $value . ' MB';
                break;

            case '1':
                $value = $value . ' GB';
                break;

            case '2':
                $value = $value . ' TB';
                break;

            default:
                $value = '';
                break;
        }

        return  $value;
    }
}
