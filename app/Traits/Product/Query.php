<?php

namespace App\Traits\Product;
use App\Models\Lead;

/**
* Product Methods model.
* Author: Sandeep Bangarh
*/

trait Query
{
    static function updateData($conditions, $data) {
		return self::where($conditions)->update($data);
	}

    static function deleteProduct ($conditions) {
        return self::where($conditions)->delete();
    }

    static function addData($data) {
		return self::create($data);
	}

    static function insertData($data) {
		return self::insert($data);
	}
}