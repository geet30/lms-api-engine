<?php

namespace App\Models;

use App\Repositories\Affiliate\PlanCrud as AffiliatePlanCrud;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Energy\GetAvailableProvider;
use App\Repositories\Energy\GetPlans;
use App\Repositories\Energy\ElecPlanCalculation;
use App\Repositories\Energy\PlanDmo;
use App\Models\{Providers,EnergyPlanRate,SolarRate,PlanTag,
};

class PlanEnergy extends Model
{
    use HasFactory,GetAvailableProvider,GetPlans,ElecPlanCalculation,PlanDmo;

    protected $appends = ['plan_name'];
    protected $table = 'plans_energy';
    protected $fillable = [
        'name', 'plan_document', 'solor_price', 'plan_type', 'plan_desc', 'energy_type', 'green_options', 'green_options_desc', 'solar_compatible', 'solar_desc', 'contract_length', 'benefit_term', 'exit_fee', 'late_payment_fee', 'cooling_off_period', 'other_fee_section', 'paper_bill_fee', 'counter_fee', 'credit_card_service_fee', 'pay_day_discount', 'pay_day_discount_usage', 'pay_day_discount_usage_desc', 'pay_day_discount_supply', 'pay_day_discount_supply_desc', 'gurrented_discount_usage', 'gurrented_discount_usage_desc', 'gurrented_discount_supply', 'gurrented_discount_supply_desc', 'direct_debit_discount_usage', 'direct_debit_discount_supply', 'direct_debit_discount_desc', 'plan_bonus', 'plan_bonus_desc', 'billing_options', 'payment_options', 'plan_features', 'terms_condition', 'provider_id', 'is_deleted', 'status', 'view_discount', 'view_bonus', 'view_exit_fee', 'view_benefit', 'view_contract', 'distributor',
        'plan_campaign_code',
        'offer_type',
        'product_code_e',
        'product_code_g',
        'plan_offer_status',
        'plan_offer',
        'eligibility',
        'campaign_code_res_elec',
        'campaign_code_res_gas',
        'campaign_code_sme_elec',
        'campaign_code_sme_gas',
        'remarketing_allow',
        'promotion_code',
        'apply_now_content',
        'apply_now_status',
        'upload_date',
        'active_on',
        'is_bundle_dual_plan',
        'bundle_code',
        'offer_code',
        'show_solar_plan',
        'show_price_fact',
        'recurring_meter_charges',
        'credit_bonus',
        'generate_token',
        'dual_only',
        'demand_usage_check'
    ];
    public function rate()
    {
        return $this->hasMany(EnergyPlanRate::class, 'plan_id', 'id');
    }
    public function planSolarRate()
    {
        return $this->hasMany(SolarRate::class, 'Plan_id', 'id');
    }
    public function planSolarRateNormal()
    {
        return $this->hasOne(SolarRate::class, 'Plan_id', 'id')->where('status',1)->where('type',1);
    }
    public function planSolarRatePermimum()
    {
        return $this->hasOne(SolarRate::class, 'Plan_id', 'id')->where('status',1)->where('type',2);
    }
    public function getPlanTags()
    {
        return $this->hasMany(PlanTag::class, 'Plan_id', 'id');
    }
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id', 'user_id');
    }
    public function planEicContents()
    {
        return $this->hasOne('App\Models\PlanEicContent', 'plan_id', 'id')->where('status', 1);
    }

    static function checkPlanExists($request, $energyType)
    {
        if($energyType == 2){
           $plan= Self::where('id', $request->gas_plan_id)->where('energy_type',$energyType)->with('provider')->first();
            return $plan;
        }

        
        return  Self::where('id', $request->plan_id)->where('energy_type', $energyType)->with([
            'rate' => function ($query) use ($request,$energyType) {
                if($energyType == 1){
                    $query->where('type', $request->elec_tariff_type);
                }
               
            },
            'provider'
        ])->first();
    }

    public function getPlanNameAttribute(){
        return $this->name;
    }
}
