<?php

namespace App\Traits\Lead;

use App\Models\{SaleProductsEnergy, SaleProductsBroadband, SaleProductsMobile, LeadJourneyDataBroadband, LeadJourneyDataMobile, LeadJourneyDataMobileHandset, Visitor,VisitorIdentification,LeadEmploymentDetails,VisitorAddress,VisitorBankInfo,VisitorDebitInfo};
use App\Models\Energy\EnergyLeadJourney;

/**
 * Lead Relationship model.
 * Author: Sandeep Bangarh
 */

trait Relationship
{
    public function affiliateName()
    {
        return $this->belongsTo('App\Models\Affiliate', 'affiliate_id', 'user_id');
    }

    public function energy()
    {
        return $this->hasMany(SaleProductsEnergy::class, 'lead_id', 'lead_id');
    }

    public function broadband()
    {
        return $this->hasMany(SaleProductsBroadband::class, 'lead_id', 'lead_id');
    }

    public function mobile()
    {
        return $this->hasMany(SaleProductsMobile::class, 'lead_id', 'lead_id');
    }

    public function energy_lead_jounery()
    {
        return $this->hasMany(EnergyLeadJourney::class, 'lead_id', 'lead_id');
    }

    public function broadband_lead_jounery()
    {
        return $this->hasOne(LeadJourneyDataBroadband::class, 'lead_id', 'lead_id');
    }

    public function mobile_lead_jounery()
    {
        return $this->hasOne(LeadJourneyDataMobile::class, 'lead_id', 'lead_id');
    }

    public function getMobileLeadHandsetData()
    {
        return $this->hasMany(LeadJourneyDataMobileHandset::class, 'lead_id', 'lead_id')->select('lead_id', 'handset_id', 'variant_id');
    }
    public function visitor()
    {
        return $this->belongsTo(Visitor::class, 'visitor_id', 'id');
    }
    public function visitorIdentification()
    {
        return $this->hasMany(VisitorIdentification::class, 'lead_id', 'lead_id');
    }
    public function visitorEmployement()
    {
        return $this->hasOne(LeadEmploymentDetails::class, 'lead_id', 'lead_id')->where('employment_type',1);
    }
    public function visitorPreviousEmployement()
    {
        return $this->hasMany(LeadEmploymentDetails::class, 'lead_id', 'lead_id')->where('employment_type',2);
    }
    
    public function visitorCurrentAddress()
    {
        return $this->hasOne(VisitorAddress::class, 'id', 'connection_address_id');
    }
    public function visitorPreviousAddress()
    {
        return $this->hasMany(VisitorAddress::class, 'visitor_id', 'visitor_id');
    }
    public function visitorBillingAddress()
    {
        return $this->hasOne(VisitorAddress::class, 'id', 'billing_address_id');
    }
    public function visitorDeliveryAddress()
    {
        return $this->hasOne(VisitorAddress::class, 'id', 'delivery_address_id');
    }
}
