<?php

namespace App\Traits\MobilePlan;

use Illuminate\Support\Facades\DB;
use App\Models\{ConnectionType, InternalStorage, Contract, Brand, Provider, PlanMobile, PlanHandset, Handset};
use Illuminate\Support\Facades\Auth;


trait BasicCrudMethods
{
    /**
     * Date:(10-March-2022)
     * get mobile screen data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    static function getMobileFilters($request)
    {
        try {
            $cacheKey = Provider::getCacheKey($request, 'mobile:filters:');
            $cacheData = Provider::getDataFromCache($cacheKey);
            if ($cacheData) {
                return $cacheData;
            }

            $data = DB::table('connection_types')->where([
                'service_id' => ConnectionType::SERVICE_MOBILE,
                'is_deleted' => ConnectionType::ZERO,
                'status' => ConnectionType::ONE
            ])->whereIn('connection_type_id', [ConnectionType::CONNECTION_TYPE_ID_ONE, ConnectionType::CONNECTION_TYPE_ID_TWO, ConnectionType::CONNECTION_TYPE_ID_THREE, ConnectionType::CONNECTION_TYPE_ID_SIX, ConnectionType::CONNECTION_TYPE_ID_SEVEN])->get()->toArray();
            $response = [];
            $response['plan_cost'] = [];
            $response['data_usage'] = [];
            $response['connection_type'] = [];
            $response['plan_type'] = [];
            $response['current_providers'] = [];
            // $response['sim_type'] = [];
            foreach ($data as $row) {
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_ONE == $row->connection_type_id) {
                    array_push($response['connection_type'], [
                        'id'       => $row->id,
                        'local_id' => $row->local_id,
                        'name'     => $row->name
                    ]);
                }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_TWO == $row->connection_type_id) {
                    array_push($response['plan_type'], [
                        'id' => $row->id,
                        'local_id' => $row->local_id,
                        'name' => $row->name
                    ]);
                }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_THREE == $row->connection_type_id) {
                    array_push($response['current_providers'], [
                        'id' => $row->id,
                        'local_id' => $row->local_id,
                        'name' => $row->name,
                        'logo' => $row->logo
                    ]);
                }
                // if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_FIVE == $row->connection_type_id) {
                //     array_push($response['sim_type'], [
                //         'id' => $row->id,
                //         'local_id' => $row->local_id,
                //         'name' => $row->name,
                //     ]);
                // }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_SIX == $row->connection_type_id && $row->local_id == 1) {
                    array_push($response['plan_cost'], [
                        'id' => $row->id,
                        'step' => (int)$row->name,
                    ]);
                }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_SIX == $row->connection_type_id && $row->local_id == 2) {
                    array_push($response['plan_cost'], [
                        'id' => $row->id,
                        'max_range' => (int)$row->name,
                    ]);
                }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_SIX == $row->connection_type_id && $row->local_id == 3) {
                    array_push($response['plan_cost'], [
                        'id' => $row->id,
                        'min_range' => (int)$row->name,
                    ]);
                }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_SEVEN == $row->connection_type_id && $row->local_id == 1) {
                    array_push($response['data_usage'], [
                        'id' => $row->id,
                        'step' => (int)$row->name,
                    ]);
                }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_SEVEN == $row->connection_type_id && $row->local_id == 2) {
                    array_push($response['data_usage'], [
                        'id' => $row->id,
                        'max_range' => (int)$row->name,
                    ]);
                }
                if (ConnectionType::SERVICE_MOBILE == $row->service_id && ConnectionType::CONNECTION_TYPE_ID_SEVEN == $row->connection_type_id && $row->local_id == 3) {
                    array_push($response['data_usage'], [
                        'id' => $row->id,
                        'min_range' => (int)$row->name,
                    ]);
                }
            }
            if ($request->detail == ConnectionType::ONE) {
                $response['contract'] = Contract::where('status', Contract::ONE)->whereNull('deleted_at')->select('id', 'validity')->orderBy('validity', 'ASC')->get()->toArray();
            }
            Provider::addDataIntoCache($cacheKey, $response);
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    /**
     * Date:(10-March-2022)
     * get mobile brand data
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    static function getphonesList($request)
    {
        try {
            $response = [];
            $response['brands'] = Brand::where('status', Brand::ONE)->where('os_name', 'Android')->whereNull('deleted_at')->select('id AS Id', 'title')->orderBy('title', 'ASC')->get()->toArray();
            $encrypt_api  = encryptGdprData($request->header('API-KEY'));
            //$affiliate_id = DB::table('affiliate_keys')->where('api_key', $encrypt_api)->pluck('user_id')->first();
            /*
               source = affiliate
               relation = provider
            */
            $relation_type = 4;
            if (Auth::user()->affiliate->parent_id == Provider::ZERO) {
                $relation_type = 1;
            }

            $provider_id = DB::table('providers')->join('assigned_users', 'providers.user_id', 'assigned_users.relational_user_id')->where(
                [
                    'assigned_users.source_user_id' => Auth::user()->id,
                    'assigned_users.service_id'     => Provider::SERVICE_MOBILE,
                    'providers.status'              => Provider::STATUS_ENABLE,
                    'providers.is_deleted'          => Provider::ZERO,
                    'assigned_users.relation_type'  => $relation_type
                ]
            )->pluck('providers.user_id');
            $responses = Provider::with([
                'getHandset' => function ($q) {
                    $q->groupBy('id');
                }, 'getHandset.variant' => function ($q) {
                    $q->select('id', 'variant_id', 'capacity_id', 'internal_stroage_id', 'color_id');
                }, 'getHandset.handset' => function ($q) {
                    $q->select('id', 'name', 'image', 'is_pre_order', 'os', 'status');
                }, 'getHandset.variant.internal' => function ($q) {
                    $q->select('id', 'unit', 'value');
                }, 'getHandset.variant.color' => function ($q) {
                    $q->select('id', 'title');
                },
                'getHandset.variant.capacity' => function ($q) {
                    $q->select('id', 'unit', 'value');
                }
            ])->whereIn('user_id', $provider_id)->get()->groupBy('user_id');
            $data = [];
            $key  = [];
            foreach ($responses as $rows) {
                foreach ($rows[0]->getHandset as $row) {
                    if ($row->handset->status == 1) {
                        if (!isset($data[$row->handset_id])) {
                            $key[0] = $row->handset_id;
                        }
                        if (!isset($data[$row->handset_id]) || !in_array($row->handset_id, $key)) {
                            $data[$row->handset_id]['variant'] = [];
                            array_push($key, $row->handset_id);
                        }
                        array_push($data[$row->handset_id]['variant'], [
                            'id' => $row->variant->id,
                            'capacity'   => isset($row->variant->capacity->capacity_name) ? $row->variant->capacity->capacity_name : '',
                            'color'      => isset($row->variant->color->title) ? $row->variant->color->title : '',
                            'storage'    => isset($row->variant->internal->storage_name) ? $row->variant->internal->storage_name : '',
                            'variant_id' => isset($row->variant->variant_id) ? $row->variant->variant_id : ''
                        ]);
                        $data[$row->handset_id] = array_merge($data[$row->handset_id], $row->handset->toArray());
                    }
                }
            }
            //return $data;
            //return $key;
            //   return $response;

            // dd($response[219][0]->getHandset->groupBy('handset_id')->toArray());
            // return $response;
            // $plan_id = DB::table('providers')
            //     ->join('assigned_users', 'providers.user_id', 'assigned_users.relational_user_id')
            //     ->join('plans_mobile', 'plans_mobile.provider_id', 'providers.user_id')->where(
            //         [
            //             'assigned_users.source_user_id' => Auth::user()->id,
            //             'assigned_users.service_id'     => Provider::SERVICE_MOBILE,
            //             'providers.status'              => Provider::STATUS_ENABLE,
            //             'providers.is_deleted'          => Provider::ZERO,
            //             'assigned_users.relation_type'  => $relation_type,
            //             'plans_mobile.plan_type'        => 2,
            //             'plans_mobile.status'        => 1

            //         ]
            //     )->pluck('plans_mobile.id');
            // $plan_handset_id = PlanHandset::whereIn('plan_id', $plan_id)->pluck('handset_id')->toArray();
            // $handsets = Handset::whereIn('id', $plan_handset_id)->where('status', 1)->whereNull('deleted_at')->select('id', 'brand_id', 'name', 'model', 'is_pre_order', 'image', 'os')->with([
            //     'brand' => function ($q) {
            //         $q->select('id', 'title');
            //     }, 'variants','variants.color','variants.capacity','variants.internal'
            // ]);
            //    $handsets = $handsets->orderBy('name', 'ASC')->get(); //dd($handsets);
            //  $result = self::getHandsetVariantsWithPlanCount($response);
            $response['all_phones'] = array_values($data);
            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'data' => $e->getMessage(),
            ], HTTP_STATUS_SERVER_ERROR);
        }
    }
    static function getHandsetVariantsWithPlanCount($handsets)
    {
        $response = [];
        $variants_array = [];
        $total_count = 0;
        foreach ($handsets as $key => $value) {
            $variant_unique_arr = [];
            $temp_data = [];
            $temp_data['brand_name'] = $value->brand->title;
            $temp_data['handset_name'] = $value->name;
            $temp_data['handset_id'] = $value->id;
            $temp_data['is_pre_order'] = $value->is_pre_order;
            $temp_data['image'] = $value->image;
            $temp_data['os'] = $value->os;
            $plan_count = 0;
            foreach ($value->variants as $id => $val) {
                $temp_data['handset_variant'][$id]['id'] = $val->id;
                $temp_data['handset_variant'][$id]['variant_id'] = $val->variant_id;
                $temp_data['handset_variant'][$id]['variant_name'] = $val->variant_name;
                $temp_data['handset_variant'][$id]['color'] = $val->color->title;
                $temp_data['handset_variant'][$id]['capacity'] = $val->capacity->capacity_name;
                $temp_data['handset_variant'][$id]['storage'] = $val->internal->storage_name;
            }
            // $temp_data['color'] = $value->variants[0]->color;
            // $temp_data['capacity'] = $value->variants[0]->capacity;
            // $temp_data['internal'] = $value->variants[0]->internal;
            // foreach ($value->variants as $k => $val) {
            //     // check if any ram, storage variant are duplicate or not. select only unique ram, storage combination.
            //     if (!in_array($val->capacity_id . $val->internal_stroage_id, $variant_unique_arr)) {
            //         $variant_unique_arr[] = $val->capacity_id . $val->internal_stroage_id;
            //         //$count = self::variantPlanCount($val->handset_id, $val->id);
            //         // if plan count come only then assign variant to array.
            //         // if ($count) {
            //         //     $plan_count = $count;
            //         //     $total_count = $total_count + $count;
            //         // }
            //     }
            // }
            $temp_data['plan_count'] = $plan_count;
            // if ($plan_count > 0) {
            $variants_array[] = $temp_data;
            //}
        }
        $response['variants_array'] = $variants_array;
        $response['total_count'] = $total_count;
        //dd($response);
        return $response;
    }
}
