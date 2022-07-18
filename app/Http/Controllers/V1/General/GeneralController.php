<?php

namespace App\Http\Controllers\V1\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{Lead, AffiliateKeys};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GeneralController extends Controller
{

    /**
     * Get Affiliate sale status.
     * Author: Sandeep Bangarh
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    function getAffiliateSaleStatus(Request $request)
    {
        try {
            $isServiceIdExist = Lead::isServiceInRequest();
            $service = Lead::getService();
            Lead::$start = request('start', 0);
            Lead::$end = request('end', 100);
            $leadFinalData = [];
            $keyData = AffiliateKeys::getDataWithUser($request->header('API-Key'), ['users.id']);
            if ($keyData) {
                $services = [$service];
                if (!$isServiceIdExist) {
                    $services = ['energy', 'mobile', 'broadband'];
                }
                $marketingColumns = ['cui', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid', 'customer_user_id'];
                $totalSales = ['energy' => 0, 'mobile' => 0, 'broadband' => 0];
                foreach ($services as $service) {
                    Lead::$service = $service;
                    $planTable = 'plans_' . $service;
                    if ($service == 'broadband') $planTable = 'plans_broadbands';

                    $columns = [
                        'sale_products_' . $service . '.id as product_id', 'reference_no', 'product_type', 'visitor_addresses.address', 'leads.lead_id as lead_id', 'providers.name as provider_name', 'affiliates.company_name as affiliate_name', 'subaff.company_name as subaffiliate_name', 'subaff.referal_code', 'sale_created', 'sale_status', 'sale_sub_status', 'sale_products_' . $service . '.service_id', $planTable . '.name as plan_name', 'commission_status'
                    ];
                    $columns = array_merge($columns, $marketingColumns);

                    if ($service == 'energy') {
                        array_push($columns, 'nmi_number');
                        array_push($columns, 'dpi_mirn_number');
                        array_push($columns, 'property_type');
                    }

                    if ($service == 'mobile') {
                        array_push($columns, $planTable . '.plan_type');
                        array_push($columns, $planTable . '.sim_type');
                        array_push($columns, 'connection_types.name as connection_name');
                    }

                    if ($service == 'broadband') {
                        array_push($columns, 'connection_types.name as connection_name');
                    }

                    $conditions = [['affiliate_id', $keyData->id]];

                    foreach ($marketingColumns as $marketingColumn) {
                        if ($request->filled($marketingColumn)) {
                            array_push($conditions, [$marketingColumn, $request->{$marketingColumn}]);
                        }
                    }

                    if ($request->filled('start_date') && $request->filled('end_date')) {
                        $startDate = Carbon::createFromFormat('d/m/Y', $request->start_date)->startOfDay()->format('Y-m-d H:i:s');
                        $endDate = Carbon::createFromFormat('d/m/Y', $request->end_date)->startOfDay()->format('Y-m-d H:i:s');

                        array_push($conditions, ['sale_created', '>=', $startDate]);
                        array_push($conditions, ['sale_created', '<=', $endDate]);
                    }

                    $leadData = Lead::getData(
                        $conditions,
                        $columns,
                        null,
                        /** With Visitor **/
                        true,
                        /** With Product **/
                        true,
                        /** With Journey **/
                        null,
                        /** With Connection **/
                        null,
                        /** With BillDetails **/
                        true,
                        /** With Address **/
                        true,
                        /** With Marketing **/
                        true,
                        /** With Provider **/
                        true,
                        /** With Affiliate and Sub Affiliate **/
                        true,
                        /** With Visitor info or identifications **/
                        true
                        /** With Plan info **/
                    );

                    $statuses = DB::table('statuses')->select('id', 'title', 'type')->where('status', 1)->get();
                    $saleStatus = array_column($statuses->where('type', 1)->toArray(), 'title', 'id');
                    $saleSubStatus = array_column($statuses->where('type', 2)->toArray(), 'title', 'id');

                    foreach ($leadData as $key => $lead) {
                        $leadData[$key]->sale_status_description = isset($saleStatus[$lead->sale_status]) ? $saleStatus[$lead->sale_status] : 'N/A';
                        $leadData[$key]->sub_status_description = isset($saleSubStatus[$lead->sale_sub_status]) ? $saleSubStatus[$lead->sale_sub_status] : 'N/A';
                        if ($service == 'energy') {
                            $leadData[$key]->property_type_description = $lead->property_type == 1 ? 'Business' : 'Residential';
                            $leadData[$key]->product_type = $lead->product_type == 1 ? 'Electricity' : 'Gas';
                            if ($lead->product_type == 1) {
                                $leadData[$key]->nmi_mirn_number = $lead->nmi_number ?? "N/A";
                            }
                            if ($lead->product_type == 2) {
                                $leadData[$key]->nmi_mirn_number = $lead->dpi_mirn_number ?? "N/A";
                            }
                        }
                        if ($service == 'mobile') {
                            if ($lead->plan_type == 2) {
                                $lead->plan_type = 'Sim + Mobile';
                            }

                            if ($lead->plan_type == 1) {
                                $lead->plan_type = 'Sim';
                            }

                            if ($lead->sim_type == 1) {
                                $lead->sim_type = 'Physical sim';
                            }

                            if ($lead->sim_type == 2) {
                                $lead->sim_type = 'Esim';
                            }

                            if ($lead->sim_type == 3) {
                                $lead->sim_type = 'Physical sim + Esim';
                            }
                        }
                        $leadData[$key]->service_name = $service;
                        switch ($lead->commission_status) {
                            case 1:
                                $leadData[$key]->affiliate_commission_status = 'Affiliate Payable';
                                break;
                            case 2:
                                $leadData[$key]->affiliate_commission_status = 'Affiliate Paid';
                                break;
                            default:
                                $leadData[$key]->affiliate_commission_status = 'N/A';
                                break;
                        }
                    }
                    $leads = array_values($leadData->toArray());

                    $totalSales[$service] = count($leads);
                    $leadFinalData = array_merge($leadFinalData, $leads);
                }
                if (empty($leadFinalData)) {
                    return errorResponse('no sale record found.', HTTP_STATUS_NOT_FOUND, POSTBACK_ERROR_CODE);
                }
                return successResponse('Sale(s) detail found successfully.', POSTBACK_SUCCESS_CODE, ['total_sales' => $totalSales, 'saleData' => $leadFinalData]);
            }
            return errorResponse('Some thing went wrong with API key', HTTP_STATUS_NOT_FOUND, POSTBACK_ERROR_CODE);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . ' in File: ' . $e->getFile() . ' in Line no: ' . $e->getLine(), HTTP_STATUS_NOT_FOUND, POSTBACK_ERROR_CODE);
        }
    }
}
