<?php


namespace App\Http\Controllers\V1\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Common\VisitConnectionRequest;
use App\Models\{Lead, LeadOtp, AffiliateTemplate, Visitor, MobileConnectionDetails, SaleProductsEnergy};
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\{Auth, DB};
use App\Traits\CommonApi\BasicCrudMethods;
use Carbon\Carbon;

class OtpController extends Controller
{
	use BasicCrudMethods;

	/**
	 * Verify OTP.
	 * Author: Sandeep Bangarh
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function verifyOtp(Request $request)
	{
		try {
			DB::beginTransaction();
			$validator = Validator::make($request->all(),  LeadOtp::rules(), LeadOtp::messages());
			if ($validator->fails()) {
				return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
			}

			$leadId = decryptGdprData($request->visit_id);
			$service = Lead::getService();
			$columns = ['visitors.*', 'sale_products_' . $service . '.product_type', 'leads.status', 'leads.visitor_id', 'leads.sale_created', 'affiliate_id', 'sub_affiliate_id', 'billing_address_id', 'reference_no', 'visitor_addresses.address', 'suburb', 'state', 'postcode'];
			if (in_array($service, ['energy','mobile'])) {
				array_push($columns, 'sale_product_' . $service . '_connection_details.*'); 
			}
			
			$visitor = Lead::getFirstLead(['leads.lead_id' => $leadId], $columns, true, true, true, true, null, true);

			if (!$visitor) {
				return errorResponse(['visit_id' => ['Visitor Not Found']], HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
			}

			if ($visitor->status == 2) {
				return successResponse('Sale is already created for this visitor.', OTP_SUCCESS_CODE, ['visitor_status' => $visitor->status]);
			}

			$otpObj = LeadOtp::getData(['lead_id' => $leadId], ['otp']);
			if (!$otpObj && $request->otp != config('app.OTP_PWD')) return errorResponse(['otp' => ['Resend OTP again']], HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);

			if ($otpObj && $otpObj->otp != $request->otp && $request->otp != config('app.OTP_PWD')) {
				return errorResponse(['otp' => ['Invalid OTP']], HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
			}

			if (!$request->has('otp_from')) {
				$eicRules = Lead::eicRules();
				$validator = Validator::make($request->all(),  $eicRules['rules'], $eicRules['msgs']);
				if ($validator->fails()) {
					return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
				}

				Lead::updateData(['lead_id' => $leadId], ['status' => 2, 'sale_created' => Carbon::now()]);

				$model = getProductModel();
				$model::updateData(['lead_id' => $leadId], ['sale_status' => 1,'sale_created_at' => Carbon::now()]);

				$columns = ['id as product_id', 'lead_id', 'service_id', 'product_type', 'plan_id', 'provider_id', 'cost', 'reference_no'];
				$mobileColumns = ['handset_id', 'variant_id', 'own_or_lease', 'contract_id'];
				$energyColumns = ['id as product_id', 'lead_id', 'service_id', 'product_type', 'plan_id', 'provider_id', 'reference_no'];
				$verticals = ['energy' => $energyColumns, 'mobile' => array_merge($columns, $mobileColumns), 'broadband' => $columns];
				$products = Lead::getProducts($verticals, $leadId, ['user_id', 'legal_name'], ['id', 'name', 'nbn_key_url', 'plan_document', 'show_price_fact', 'terms_condition'], true);

				$products = collect($products);
				$referenceNos = Lead::addReferenceNo($leadId, $products);

				Lead::addReconSale($leadId, $products, $visitor, $referenceNos);

				AffiliateTemplate::sendEmailAndSms($products, $visitor, $referenceNos);
				// Lead::sendDataToDialler();

				DB::commit();
				return successResponse('OTP verified successfully.',  OTP_SUCCESS_CODE, ['checkbox_data' => $eicRules['saveCheckboxStatusData']]);
			} else {
				DB::commit();
				return successResponse('OTP verified successfully.',  OTP_SUCCESS_CODE, []);
			}
		} catch (\Exception $e) {
			DB::rollback();
			return errorResponse($e->getMessage() . " on line:" . $e->getLine() . ' file: ' . $e->getFile(), $e->getCode(), OTP_ERROR_CODE, __FUNCTION__);
		}
	}

	/**
	 * Send OTP.
	 * Author: Sandeep Bangarh
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendOtp(Request $request)
	{
		try {
			LeadOtp::setLead($request);
			$validator = Validator::make($request->all(),  LeadOtp::sendOtpRules(), LeadOtp::sendOtpMessages());
			if ($validator->fails()) {
				return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
			}

			$leadId = decryptGdprData($request->visit_id);
			$visitor = LeadOtp::getLead();

			if (!$visitor) {
				return errorResponse(['visit_id' => ['Visitor Not Found']], HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
			}

			if ($visitor->status == 2) {
				return successResponse('Sale is already created for this visitor.', OTP_SUCCESS_CODE, ['visitor_status' => $visitor->status]);
			}
			$service = Lead::getService(true);
			$data = null;

			if ($service == 1) {
				$data = Visitor::getOTPEnergyData($visitor, $leadId);
			}
			
			if ($service != 1) {
				$data = Visitor::getOTPData($visitor, $request, $leadId);
			}

			if ($data == 0) {
				return errorResponse('Please apply for a plan.', HTTP_STATUS_SERVER_ERROR, OTP_ERROR_CODE);
			}

			if ($data == 1) {
				return errorResponse('Variant or handset is not found.', HTTP_STATUS_SERVER_ERROR, OTP_ERROR_CODE);
			}

			$lead = [
				'affiliate_id' => Auth::user()->id,
				'connection_phone' =>  $visitor->phone,
				'phone' => $visitor->phone,
				'lead_status' => $visitor->status
			];
			LeadOtp::clearOTP($leadId);

			/** Send OTP to customer **/
			$response = self::resendOtpRequest(request(), $lead);
			if (isset($response['status']) && !$response['status']) {
				return errorResponse('Something went wrong with OTP, plesae try again.', HTTP_STATUS_VALIDATION_ERROR, OTP_ERROR_CODE);
			}
			
			// dispatch(new SendSms($lead));

			$data['service_id'] = $service;
			return successResponse('OTP sent successfully.', OTP_SUCCESS_CODE, $data);
		} catch (\Exception $e) {
			DB::rollback();
			// return errorResponse('You have not apply for plan or something went wrong with request data.', $e->getCode(), OTP_ERROR_CODE);
			return errorResponse($e->getMessage() . " on line:" . $e->getLine() . ' file: ' . $e->getFile(), $e->getCode(), OTP_ERROR_CODE, __FUNCTION__);
		}
	}

	/**
	 * Send Connection OTP.
	 * Author: Amandeep Singh
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function sendConnectionOtp(Request $request)
	{
		try {
			$request->merge([
				"service_id" => $request->header('ServiceId'),
				"otp_type" => "connection"
			]);
			$reqObj = new VisitConnectionRequest($request);
			$validator = Validator::make($request->all(), $reqObj->rules(), $reqObj->messages());
			if ($validator->fails()) {
				$data = $validator->errors();
				return $data;
			}
			if ($request->service_id == 2) {
				return MobileConnectionDetails::sendConnectionOtp($request);
			}
		} catch (\Exception $e) {
			return response()->json([
				'data' => $e->getMessage(),
			], HTTP_STATUS_SERVER_ERROR);
		}
	}

	/**
	 * Resend OTP.
	 * Author: Sandeep Bangarh
	 * @param  \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function resendOtp(Request $request)
	{
		try {
			$validator = Validator::make($request->all(),  ['visit_id' => 'required'], ['visit_id.required' => 'Visit id is required!']);
			if ($validator->fails()) {
				return errorResponse($validator->errors(), HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
			}

			$leadId = decryptGdprData($request->visit_id);
			$visitor = Lead::getFirstLead(['lead_id' => $leadId], ['status', 'phone'], true);
			if (!$visitor) {
				return errorResponse('Visitor not found', HTTP_STATUS_VALIDATION_ERROR, OTP_VALIDATION_CODE);
			}
			$lead = [
				'affiliate_id' => Auth::user()->id,
				'connection_phone' =>  decryptGdprData($visitor->phone),
				'phone' => decryptGdprData($visitor->phone),
				'lead_status' => $visitor->status
			];
			$return = self::resendOtpRequest($request, $lead);
			if ($return['status']) {
				return successResponse('OTP sent successfully.', OTP_SUCCESS_CODE);
			}
			return successResponse('Something went wrong while sending OTP.', OTP_SUCCESS_CODE);
		} catch (\Exception $e) {
			return errorResponse($e->getMessage() . " on line:" . $e->getLine() . ' file: ' . $e->getFile(), $e->getCode(), OTP_ERROR_CODE, __FUNCTION__);
		}
	}
}
