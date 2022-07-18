<?php

namespace App\Http\Controllers\V1\General;

use App\Http\Controllers\Controller;
use App\Models\ { Provider, AppSetting };

class TermAndPrivacyController extends Controller
{
    /**
     * Get Term & Condition.
     * Author: Sandeep Bangarh
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTermCondition()
    {
        try {
            $content = AppSetting::getTermOrPrivacyData(['type' => 'term'], ['attributes', 'content']);
            
            if ($content) {
                return successResponse('Content found successfully', TERM_SUCCESS_CODE, ['content' => $content]);
            }
            
            return successResponse('Term & Condition not found', TERM_SUCCESS_CODE);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), TERM_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Get Privacy Policy.
     * Author: Sandeep Bangarh
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPrivacyPolicy()
    {
        try {
            $content = AppSetting::getTermOrPrivacyData(['type' => 'privacy'], ['attributes', 'content']);
            
            if ($content) {
                return successResponse('Content found successfully', TERM_SUCCESS_CODE, ['content' => $content]);
            }

            return successResponse('Term & Condition not found', TERM_SUCCESS_CODE);

        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), TERM_ERROR_CODE, __FUNCTION__);
        }
    }

    /**
     * Get Provider term and condition.
     * Author: Sandeep Bangarh
     * @return \Illuminate\Http\JsonResponse
     */
    public function providersTermsConditions()
	{
		try {
			$termData = Provider::getProviderTermsConditions(auth()->user()->id);
            if (!empty($termData)) {
                return successResponse('Providers Terms and Conditions are found successfully.', TERM_SUCCESS_CODE, array_values($termData));
            }
            return successResponse('There is not any Terms and Conditions exist.', TERM_SUCCESS_CODE);

		} catch (\Exception $e) {
			return errorResponse($e->getMessage() . " on line:" . $e->getLine(), $e->getCode(), TERM_ERROR_CODE, __FUNCTION__);
		}
	}
}
