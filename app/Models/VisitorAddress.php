<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Visitor\Address\{Methods};

class VisitorAddress extends Model
{
    use Methods;

    protected $fillable = ['visitor_id', 'address', 'address_type', 'lot_number', 'unit_number', 'unit_type', 'unit_type_code', 'floor_number', 'floor_level_type', 'floor_type_code', 'street_number', 'street_number_suffix', 'street_name', 'street_suffix', 'street_code', 'house_number', 'house_number_suffix', 'suburb', 'state', 'postcode', 'property_name', 'residential_status', 'living_year', 'living_month', 'gnf_no'];
    protected $appends =['CompletePostCode'];
    public static $gnfMapping = [
        "AddressSiteId" => "gnf_no",
        "AddressLine" =>  "address",
        "LotNumber" => "lot_number",
        "UnitType" => "unit_type",
        "UnitNumber" => "unit_number",
        "LevelType" => "floor_level_type",
        "LevelNumber" => "floor_number",
        "StreetNumber1" => "street_number",
        "StreetNumberSuffix1" => "street_number_suffix",
        "StreetName" => "street_name",
        "StreetType" => "street_code",
        "StreetSuffix" => "street_suffix",
        "Locality" => "suburb",
        "State" => "state",
        "Postcode" => "postcode",
        "LegalParcelId" => "house_number",
        "PropertyId" => "property_name"
    ];

    function getCompletePostCodeAttribute(){
            return $this->postcode.','.$this->suburb.','.$this->state;
    }
}
