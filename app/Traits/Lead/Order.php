<?php

namespace App\Traits\Lead;

use Illuminate\Support\Facades\{Auth, DB};
use App\Models\{Lead, AppSetting, Service, PlanVariant, Provider};

/**
 * Lead Order model.
 * Author: Sandeep Bangarh
 */

trait Order
{
    /**
     * Get Products.
     * Author: Sandeep Bangarh
     */
    static function getProducts($verticals, $leadId, $withProvider = null, $withPlan = null, $beautifyResponse = null, $withAddon = null)
    {
        $query = Lead::select(['lead_id', 'status', 'visitor_id']);
        $data = ['providerColumns' => $withProvider, 'planColumns' => $withPlan];
        foreach ($verticals as $relation  => $columns) {
            $data['columns'] = $columns;
            $data['relation'] = $relation;
            $data['withAddon'] = $withAddon;
            $query = $query->with($relation, function ($query) use ($data) {
                $query->whereNotNull('plan_id')->where('plan_id', '!=', '');
                $query->select($data['columns']);
                if ($data['providerColumns']) {
                    $query->with(['provider' => function ($qu) use ($data) {
                        foreach($data['providerColumns'] as $key => $column) {
                            $providerModel = new Provider;
                            if ($column != 'id' && !in_array($column, $providerModel->getFillable())) {
                                unset($data['providerColumns'][$key]);
                            }
                        }
                        $qu->select($data['providerColumns']);
                    }]);
                }

                if ($data['withAddon'] && $data['relation'] == 'broadband') {
                    $query = static::broadbandRelations($query);
                }

                if ($data['relation'] == 'mobile') {
                    $query = static::mobileRelations($query);
                }

                if ($data['planColumns']) {
                    $query->with(['plan' . ucfirst($data['relation']) => function ($qu) use ($data) {
                        $model = self::getModel($data['relation']);
                        $planModel = new $model;
                        foreach($data['planColumns'] as $key => $column) {
                            if ($column != 'id' && !in_array($column, $planModel->getFillable())) {
                                unset($data['planColumns'][$key]);
                            }
                        }
                        $qu->select($data['planColumns']);
                    }]);
                }
            });
        }

        $all = $query->find($leadId);
        if ($all && $beautifyResponse) {
            $finalData = [];
            foreach (array_keys($verticals) as $vertical) {
                if (!$all->{$vertical}->isEmpty()) {
                    $verticalArray = $all->{$vertical}->toArray();
                    if ($withAddon && $withPlan && $vertical == 'broadband') {
                        foreach ($verticalArray as $vertKey => $verticalData) {
                            foreach ($verticalData['addons'] as $addonKey => $addon) {
                                $addon = static::getAddonItemByCategory($addon);
                                $addon = static::costType($addon);
                                $verticalData['addons'][$addonKey] = $addon;
                            }
                            $verticalArray[$vertKey] = $verticalData;
                        }
                    }
                    if ($vertical == 'mobile') {
                        $verticalArray = static::unsetMobileData($verticalArray);
                    } else {
                        foreach ($verticalArray as $vertKey => $verticalData) {
                            $verticalArray[$vertKey] = static::unsetData($verticalArray[$vertKey]);
                        }
                    }

                    $finalData = array_merge($finalData, $verticalArray);
                }
            }
            return $finalData;
        }

        return $all;
    }

    static function getModel ($relation) {
        switch ($relation) {
            case 'energy':
                $model = '\App\Models\PlanEnergy';
                break;
            case 'mobile':
                $model = '\App\Models\PlanMobile';
                break;
            case 'broadband':
                $model = '\App\Models\PlanBroadband';
                break;
            
            default:
                $model = null;
                break;
        }
        return $model;
    }

    static function unsetMobileData($verticalArray)
    {
        foreach ($verticalArray as $vertKey => $verticalData) {
            unset($verticalArray[$vertKey]['handset']['id']);
            unset($verticalArray[$vertKey]['variant']['id']);
            unset($verticalArray[$vertKey]['contract']['id']);
            unset($verticalArray[$vertKey]['color']['id']);
            unset($verticalArray[$vertKey]['plan_mobile']['id']);
            unset($verticalArray[$vertKey]['variant']['capacity_id']);
            unset($verticalArray[$vertKey]['variant']['internal_stroage_id']);
            unset($verticalArray[$vertKey]['variant']['capacity']['id']);
            unset($verticalArray[$vertKey]['variant']['internal']['id']);
            unset($verticalArray[$vertKey]['variant']['color_id']);
            unset($verticalArray[$vertKey]['variant']['color']['id']);

            $verticalArray[$vertKey] = static::unsetData($verticalArray[$vertKey]);
        }
        return $verticalArray;
    }

    static function unsetData($verticalArray)
    {
        unset($verticalArray['id']);
        // unset($verticalArray['plan_id']);
        // unset($verticalArray['handset_id']);
        // unset($verticalArray['variant_id']);
        // unset($verticalArray['contract_id']);
        return $verticalArray;
    }

    static function mobileRelations($query)
    {
        $query->with(
            [
                'handset' => function ($query) {
                    $query->select('id', 'name','image');
                },
                'variant' => function ($query) {
                    $query->select('id', 'variant_name', 'capacity_id', 'internal_stroage_id', 'color_id');
                },
                'contract' => function ($query) {
                    $query->select('id', 'contract_name','validity');
                },
                'variant.capacity' => function ($query) {
                    $query->select('id','value','unit');
                },
                'variant.internal' => function ($query) {
                    $query->select('id','value','unit');
                },
                'variant.color' => function ($query) {
                    $query->select('id', 'title');
                }
            ]
        );
        return $query;
    }

    static function broadbandRelations($query)
    {
        $query->with(
            [
                'addons.homeConnection' => function ($query) {
                    $query->select('id', 'call_plan_name as name');
                },
                'addons.broadBandModem' => function ($query) {
                    $query->select('id', 'modem_modal_name as name');
                },
                'addons.broadBandOtherAddon' => function ($query) {
                    $query->select('id', 'addon_name as name');
                },
                'addons.cost_type' => function ($query) {
                    $query->select('id', 'cost_name', 'cost_period');
                }
            ]
        );

        return $query;
    }

    static function getAddonItemByCategory($addon)
    {
        if ($addon['category_id'] == 3) {
            $addon['name'] = $addon['home_connection']['name'];
            $addon = static::unsetAll($addon);
        }

        if ($addon['category_id'] == 4) {
            $addon['name'] = $addon['broad_band_modem']['name'];
            $addon = static::unsetAll($addon);
        }

        if ($addon['category_id'] == 5) {
            $addon['name'] = $addon['broad_band_other_addon']['name'];
            $addon = static::unsetAll($addon);
        }
        return $addon;
    }

    static function costType($addon)
    {
        if ($addon['cost_type']) {
            $addon['cost_name'] = $addon['cost_type']['cost_name'];
            $addon['cost_period'] = $addon['cost_type']['cost_period'];
        }
        unset($addon['cost_type']);
        return $addon;
    }

    static function unsetAll($addon)
    {
        unset($addon['home_connection']);
        unset($addon['broad_band_modem']);
        unset($addon['broad_band_other_addon']);
        return $addon;
    }

    /**
     * Order Confirmation data.
     * Author: Sandeep Bangarh
     */
    static function orderConfirmationContent($leadId)
    {
        $user = Auth::user();
        $service = Lead::getService();
        $affilateData = $user->getAffiliate(['abn', 'parent_id', 'legal_name', 'support_phone_number', 'youtube_url', 'twitter_url', 'facebook_url', 'linkedin_url', 'google_url', 'subaccount_id', 'page_url', 'address'], true, true, true, true);

        $orderContent = [];
        $orderContent['Affiliate-Abn'] = isset($affilateData->abn) ? $affilateData->abn : '';
        $orderContent['Affiliate-Name'] = decryptGdprData($user->first_name) . ' ' . decryptGdprData($user->last_name);
        $orderContent['Affiliate-Address'] = isset($affilateData->address) ? $affilateData->address : '';
        $orderContent['Affiliate-Contact-Number'] = isset($user->phone) ? decryptGdprData($user->phone) : '';

        $columns = ['id as product_id', 'lead_id', 'service_id', 'product_type', 'plan_id', 'provider_id', 'cost', 'reference_no'];
        $energyColumns = ['id as product_id', 'lead_id', 'service_id', 'product_type', 'plan_id', 'provider_id', 'cost', 'reference_no'];
        $mobileColumns = ['handset_id', 'variant_id', 'own_or_lease'];
        $verticals = ['energy' => $energyColumns, 'mobile' => array_merge($columns, $mobileColumns), 'broadband' => $columns];
        $planColumns = ['id', 'name', 'plan_type'];
        if ($service != 'energy') {
            array_push($planColumns, 'connection_type');
        }
        $products = Lead::getProducts($verticals, $leadId, ['id', 'legal_name', 'user_id'], $planColumns, true);

        $services = Service::getServices($user->id);
        
        $visitorColumns = ['first_name', 'email', 'phone'];
        if ($service == 'energy') {
            array_push($visitorColumns, 'is_dual');
        }
        $visitor = Lead::getFirstLead(['leads.lead_id' => $leadId], $visitorColumns, true, null, true);

        return static::setPlanData($orderContent, $affilateData, $products, $services, $visitor);
    }

    /**
     * Set plan data.
     */
    static function setPlanData($orderContent, $affilateData, $products, $services, $visitor)
    {
        $appData = AppSetting::getAppSetting(['type' => 'what-happen-next'], ['attributes']);
        $attributes = explode(",", $appData->attributes);
        /** Code  for EIC content **/
        // $moveInData = [];
        // $contentAppData = AppSetting::getAppSetting(['type' => 'movein_attributes'], ['attributes']);
        // $contentAttributes = explode(",", $contentAppData->attributes);
        $whatHappenNextData = [];

        foreach ($products as $product) {
            $service = $services->where('id', $product['service_id'])->first();
            $content = DB::table('provider_contents')->select('description')->where('type', "11")->where('provider_id', $product['provider_id'])->first();
            // if (!$content) continue;
            if ($service) {
                $orderContent['Plan-Name'] = isset($product['plan_' . strtolower($service->service_title)]['name']) ? $product['plan_' . strtolower($service->service_title)]['name'] : '';
                $whatHappenNextData['legal_name'] = decryptGdprData($affilateData->legal_name);

                if ($service->id == 1) {
                    $whatHappenNextData['gas'] = $whatHappenNextData['electricity'] = [];
                    $orderContent['Provider-Name'] = isset($product['provider']['legal_name']) ? $product['provider']['legal_name'] : '';
                    $orderContent['Reference-Id'] = isset($product['reference_no']) ? $product['reference_no'] : '';
                    $orderContent['Customer-Name'] = isset($visitor->first_name) ? decryptGdprData($visitor->first_name) : '';
                    $orderContent['Customer-Email'] = isset($visitor->email) ? decryptGdprData($visitor->email) : '';
                    $orderContent['Customer-Contact_Number'] = isset($visitor->phone) ? decryptGdprData($visitor->phone) : '';
                  

                    if (isset($product['product_type']) && $product['product_type'] == 2) {
                        $whatHappenNextData['gas']['provider_name'] = isset($product['provider']['legal_name']) ? $product['provider']['legal_name'] : '';;
                        $whatHappenNextData['gas']['refrence_number'] = $product['reference_no'];
                        $whatHappenNextData['gas']['content'] = $content?str_replace($attributes, $orderContent, $content->description):'';
                        $whatHappenNextData['gas']['logo'] = isset($product['provider']['logo']) ? $product['provider']['logo'] : '';
                    }

                    if (isset($product['product_type']) && $product['product_type'] == 1) {
                        $whatHappenNextData['electricity']['provider_name'] = isset($product['provider']['legal_name']) ? $product['provider']['legal_name'] : '';;
                        $whatHappenNextData['electricity']['refrence_number'] = $product['reference_no'];
                        $whatHappenNextData['electricity']['content'] = $content?str_replace($attributes, $orderContent, $content->description):'';
                        $whatHappenNextData['electricity']['logo'] = isset($product['provider']['logo']) ? $product['provider']['logo'] : '';
                    }
                }

                if ($service->id == 2) {
                    $orderContent['Plan-Type'] = isset($product['plan_' . strtolower($service->service_title)]['plan_type']) == 1 ?  'SIM' : 'SIM + Mobile';
                    $orderContent['Connection-Type'] = isset($product['plan_' . strtolower($service->service_title)]['connection_type']) == 1 ? 'Personal'  : 'Business';
                    $orderContent['Provider-Name'] = isset($product['provider']['legal_name']) ? $product['provider']['legal_name'] : '';
                    $orderContent['Reference-Id'] = isset($product['reference_no']) ? $product['reference_no'] : '';
                    $orderContent['Customer-Name'] = isset($visitor->first_name) ? decryptGdprData($visitor->first_name) : '';
                    $orderContent['Customer-Email'] = isset($visitor->email) ? decryptGdprData($visitor->email) : '';
                    $orderContent['Customer-Contact_Number'] = isset($visitor->phone) ? decryptGdprData($visitor->phone) : '';
                    $whatHappenNextData['mobile']['provider_name'] = isset($product['provider']['legal_name']) ? $product['provider']['legal_name'] : '';;
                    $whatHappenNextData['mobile']['refrence_number'] = $product['reference_no'];
                    $whatHappenNextData['mobile']['content'] = $content?str_replace($attributes, $orderContent, $content->description):'';
                    $whatHappenNextData['mobile']['logo'] = isset($product['provider']['logo']) ? $product['provider']['logo'] : '';
                }

                if ($service->id == 3) {
                    $orderContent['Provider-Name'] = isset($product['provider']['legal_name']) ? $product['provider']['legal_name'] : '';
                    $orderContent['Reference-Id'] = isset($product['reference_no']) ? $product['reference_no'] : '';
                    $orderContent['Customer-Name'] = isset($visitor->first_name) ? decryptGdprData($visitor->first_name) : '';
                    $orderContent['Customer-Email'] = isset($visitor->email) ? decryptGdprData($visitor->email) : '';
                    $orderContent['Customer-Contact_Number'] = isset($visitor->phone) ? decryptGdprData($visitor->phone) : '';
                    $whatHappenNextData['broadband']['provider_name'] = isset($product['provider']['legal_name']) ? $product['provider']['legal_name'] : '';;
                    $whatHappenNextData['broadband']['refrence_number'] = $product['reference_no'];
                    $whatHappenNextData['broadband']['content'] = $content?str_replace($attributes, $orderContent, $content->description):'';
                    $whatHappenNextData['broadband']['logo'] = isset($product['provider']['logo']) ? $product['provider']['logo'] : '';
                }
            }
        }
        return $whatHappenNextData;
    }

    static function arrangeData ($products) {
        try {
            foreach($products as $productKey => $product) {
                $calulationArray = self::calculateCost($product);
                $products[$productKey] = array_merge($products[$productKey], $calulationArray);
            }
        } catch (\Exception $e) {
            // dd($e->getMessage().'  '. $e->getLine());
        }
        return $products;
    }

    static function calculateCost ($product) {
        $calculation = ['handset_cost'=>'','total_cost'=>'','total_monthly_cost'=>'','plan_contract'=>'','handset_contract'=>'','plan_monthly_cost'=>'','handset_month_cost'=>'','plan_cost'=>'','total_minimum_cost'=>''];
        $handsetCost = [];
        $variant = DB::table('plans_mobile_variants')->select('id','own', 'lease', 'own_cost', 'lease_cost')->where('plan_id', $product['plan_id'])->where('handset_id', $product['handset_id'])->where('variant_id', $product['variant_id'])->first();
        if ($variant) {
            $contractCost = DB::table('plans_mobile_contracts')->where('plan_variant_id', $variant->id)->select('id','contract_id', 'contract_type', 'contract_cost')->get();
        
            $ownContract = $contractCost->where('contract_type', 0)->first();
            
            if ($ownContract) {
                $contractValidity = DB::table('contract')->where('id', $ownContract->contract_id)->value('validity');
                $handsetCost['phone_contract'] = $contractValidity;
                $handsetCost['cost'] = $ownContract->contract_cost;
            }
    
            $leaseContract = $contractCost->where('contract_type', 1)->first();
            if (!$ownContract && $leaseContract) {
                $contractValidity = DB::table('contract')->where('id', $leaseContract->contract_id)->value('validity');
                $handsetCost['cost'] = $leaseContract->contract_cost;
                $handsetCost['phone_contract'] = $contractValidity;
            }
        }
        if ($product['product_type'] == 1) {
            $handsetCost['cost'] = $product['total_cost'];
            $handsetCost['phone_contract'] = $product['contract']?$product['contract']['validity']:'';
        }
        
        $totalCost = $planMonthCost =  $product['plan_mobile']['cost'];
        if (isset($product['plan_mobile']['special_offer_status']) && $product['plan_mobile']['special_offer_status'] == 1) {
            $totalCost = $planMonthCost =  $product['plan_mobile']['special_offer_cost'];
        }
        // $planMonthCost =   $product['plan_mobile']['cost'] / ($product['contract']?$product['contract']['validity']:1);
        
        
        
        $calculation['plan_contract'] = $product['contract']?$product['contract']['validity']:'';
        $calculation['plan_monthly_cost'] = round($planMonthCost, 2);
        $calculation['plan_cost'] = round($totalCost, 2);

        if ($product['product_type'] == 1) {
            $calculation['total_minimum_cost'] = round($totalCost, 2);
        }

        if ($product['product_type'] == 2) {
            $calculation['total_cost'] = round($handsetCost['cost'] + $totalCost, 2);
            $hansetTotalCost =  $handsetCost['cost'] / $handsetCost['phone_contract'];
            $calculation['handset_cost'] = round($handsetCost['cost'], 2);
            $calculation['total_monthly_cost'] = round(($hansetTotalCost + $planMonthCost), 2);
            $calculation['handset_month_cost'] = round($hansetTotalCost, 2);
            $calculation['handset_contract'] = $handsetCost['phone_contract'];
            $calculation['total_minimum_cost'] = round(($handsetCost['cost']+$planMonthCost), 2);
        }
        
        return $calculation;
    }
}
