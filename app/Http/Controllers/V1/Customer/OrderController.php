<?php

namespace App\Http\Controllers\V1\Customer;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Lead;

class OrderController
{
    /**
     * Create Order List.
     * Author: Sandeep Bangarh
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke($id)
    {
        try {
            $leadId = decryptGdprData($id);
            $columns = ['id as product_id', 'lead_id', 'service_id', 'product_type', 'plan_id', 'provider_id', 'reference_no'];
            $mobileColumns = ['handset_id','variant_id','contract_id','own_or_lease','cost as total_cost'];
            $verticals = ['energy' => $columns, 'mobile' => array_merge($columns, $mobileColumns), 'broadband' => $columns];
            $planColumns = ['id', 'name','cost','minimum_total_cost','special_offer_status','special_offer_cost'];
            $products = Lead::getProducts($verticals, $leadId, ['user_id', 'legal_name'], $planColumns, true, true);
            $products = Lead::arrangeData($products);
            return successResponse(!empty($products) ? 'Products fetched successfully' : 'Products not found', ORDER_SUCCESS_CODE, $products);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ORDER_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Order confirmation.
     * Author: Sandeep Bangarh
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['visit_id' => 'required'], ['visit_id.required' => 'Visit id is required']);
            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, ORDER_VALIDATION_CODE);
            }
            $leadId = decryptGdprData($request->visit_id);
            $orderContent = Lead::orderConfirmationContent($leadId);
            return successResponse('Order confirmation text found successfully', ORDER_SUCCESS_CODE, $orderContent);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ORDER_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Remove product.
     * Author: Sandeep Bangarh
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['product_id' => 'required', 'service_id' => 'required'], ['product_id.required' => 'Product id is required', 'service_id.required' => 'Service id is required']);
            if ($validator->fails()) {
                return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, ORDER_VALIDATION_CODE);
            }
            $model = getProductModel();
            $isDeleted = $model::deleteProduct(['id' => $request->product_id, 'service_id' => $request->service_id]);
            if ($isDeleted) {
                return successResponse('Product removed successfully', ORDER_SUCCESS_CODE);
            }

            return successResponse('Product no more exist', ORDER_SUCCESS_CODE);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), ORDER_ERROR_CODE, __FUNCTION__);
        }
    }
}
