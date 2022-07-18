<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Lead\{ Relationship, Methods };
class LeadJourneyDataMobileHandset extends Model
{
    use HasFactory,Methods, Relationship; 
}
