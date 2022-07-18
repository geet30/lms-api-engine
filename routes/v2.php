<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Version Two Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
$router->group(['middleware' => ['throttle:5,1', 'cors', 'firewall']], function () use ($router) {
    $router->group(['namespace' => 'Auth'], function () use ($router) {

        /** Generate Token **/
        $router->post('generate-token', 'GenerateTokenController');
    });

    $router->group(['middleware' => ['check-sale-status','auth']], function () use ($router) {
        // Write API's here which need token authntications
        $router->group(['prefix' => 'mobile', 'namespace' => 'Plans\Mobile'], function () use ($router) {
            $router->post('/plan/list',  ['uses' => 'MobilePlanController@planListing']);
            $router->get('/plan/terms',  ['uses' => 'MobilePlanController@planTerms']);
            $router->get('/plan/critical_info',  ['uses' => 'MobilePlanController@planCriticalInfo']);
            $router->get('/plan/filters',  ['uses' => 'MobilePlanController@getMobileFilters']);
            $router->get('/phones/list',  ['uses' => 'MobilePlanController@getphonesList']);
            $router->post('/plan/details',  ['uses' => 'MobilePlanController@planDetails']);
            $router->post('/compare-plans',  ['uses' => 'MobilePlanController@planCompareDetails']);

            // $router->post('/plan/apply',  ['uses' => 'MobilePlanController@applyNowPlan']);
        });

        $router->group(['namespace' => 'Plans'], function () use ($router) {

            $router->post('send-plan',  ['uses' => 'SendPlanController@postSendPlan']);
        });
        $router->group(['prefix' => 'energy', 'namespace' => 'Plans\Energy'], function () use ($router) {
            $router->post('plan/list',  ['uses' => 'EnergyPlanController@PostPlanListing']);
            $router->post('plan/details',  ['uses' => 'EnergyPlanController@PlanDeatils']);
            $router->post('plan/dmo',  ['uses' => 'DmoContentController@getDmoContent']);
            $router->post('movein-content',  ['uses' => 'ProviderContent@providerMoveInContent']);

            /** Get Plan BPID data **/
            $router->post('plan/bpid', 'GeneralController@planBPIDdata');

            $router->post('demand-tariff',  ['uses' => 'DemandController@getDemandTariff']);
        });

        $router->group(['namespace' => 'journeyData'], function () use ($router) {
            $router->post('journey/save',  ['uses' => 'LeadJourneyController@postSaveJourney']);
            $router->get('journey/get',  ['uses' => 'LeadJourneyController@getJourneyData']);
        });
        $router->group(['namespace' => 'AccountDetails'], function () use ($router) {
            $router->post('/tokenize/auth',  ['uses' => 'AccountController@getAuthToken']);
            $router->post('/detokenize/auth',  ['uses' => 'AccountController@deTokenizedAuth']);
            $router->post('/tokenize/savedata',  ['uses' => 'AccountController@saveAuthToken']);
        });


        $router->group(['namespace' => 'Plans'], function () use ($router) {
            $router->post('plan/apply', ['uses' => 'PlanApplyController@planApply']);
            $router->post('plan/add-sim-type', ['uses' => 'PlanApplyController@addSimType']);
        });
        $router->group(['namespace' => 'Plans\Broadband'], function () use ($router) {
            $router->get('nbn/check', 'BroadbandPlanController@getNbnData');
            $router->get('broadband/connections', 'BroadbandPlanController@getConnectionType');
            $router->post('provider/list', 'BroadbandPlanController@getProviderList');
            $router->post('/provider/eic', 'BroadbandPlanController@getEicData');
            $router->get('min-selectable-date', 'BroadbandPlanController@getMinSelectableDate');
            $router->group(['prefix' => 'broadband/plan'], function () use ($router) {
                $router->post('list',  ['uses' => 'BroadbandPlanController@getPlans']);
                $router->get('addon/list',  ['uses' => 'BroadbandPlanController@getPlansAddon']);
                $router->post('addon/save',  ['uses' => 'BroadbandPlanController@savePlansAddon']);
                $router->delete('addon/remove', ['uses' => 'BroadbandPlanController@deleteSelectedPlanAddon']);
            });
            $router->post('save-rm-utm', 'BroadbandPlanController@saveRmUtm');
            $router->post('save-satellite-questions', 'BroadbandPlanController@saveSatelliteQuestion');
        });
        $router->group(['namespace' => 'Visitor'], function () use ($router) {
            /* Save Visitor Information */
            $router->post('visit/create', 'VisitorController@saveVisit');

            /** Get Life Support Content **/
            $router->get('energy/equipments-content', 'LifeSupportController@getLifeSupportContent');

            /** Get Life Support Equipment **/
            $router->get('energy/equipments', 'LifeSupportController@getLifeSupportEquipment');

            /** Get Life Support Equipment **/
            $router->get('energy/solar-tariff', 'SolarController@getSolarTypeList');

            /* Update Visitor Information */
            $router->post('visit/progress', 'VisitorController@updateVisit');

            /* Get street codes*/
            $router->get('get-street-codes', 'VisitorController@getStreetCodes');
            /* save connection detail */
            $router->post('visit/save-connection', 'VisitorConnectionController@saveConnectionDetails');

            /*get master data*/
            $router->get('visitor/masters', 'VisitorController@getMasterDetails');

            /* save connection detail */
            $router->post('visitor/address', 'AddressController');

            
          
        });

        $router->group(['namespace' => 'Customer'], function () use ($router) {
            /* Create Customer */
            $router->post('customer/create', 'CreateUpdateController');
            $router->post('create-move-in-customer', 'CreateUpdateController@createMoveInCustomer');

            /* Update Customer */
            $router->post('customer/update', 'CreateUpdateController');

            /* Order Summary */
            $router->get('order/summary/{id}', 'OrderController');

            /* Remove product from order summary */
            $router->delete('order/product', 'OrderController@removeProduct');

            /* Search ABN address name */
            $router->get('search/abn/address', 'AbnController@searchAbnName');

            /* Search ABN number */
            $router->get('search/abn/number', 'AbnController@searchAbnNumber');

            /** It is used to update customer phone number. **/
            $router->put('customer/update-phone-number', 'CreateUpdateController@updatePhoneNumber');

            /** It is used to send OTP. **/
            $router->post('customer/send-otp', 'OtpController@sendOtp');
            
            /** It is used to send OTP for connection detail section. **/
            $router->post('customer/connection/send-otp', 'OtpController@sendConnectionOtp');

            /** Resend OTP. **/
            $router->post('customer/resend-otp', 'OtpController@resendOtp');

            /** It is used to verify OTP. **/
            $router->post('customer/verify-otp', 'OtpController@verifyOtp');

            /** Confirmation order text. **/
            $router->post('order/confirmation', 'OrderController@confirmOrder');

            /** Save identification details. **/
            $router->post('customer/identification/save', 'IdentificationController');

            /** save personal detail. **/
            $router->post('visitor/personal_detail', 'AccountController@personalDetail');
            /** save employment details */
            $router->post('employement/details', 'EmploymentController@saveEmploymentDetails');
            /*get concession type*/
            $router->get('concession/type','ConcessionController@getConcessionContent');
            $router->post('save/concession','ConcessionController@saveConcessionDetails');
        });

        $router->group(['namespace' => 'Plans\Broadband'], function () use ($router) {
            $router->get('nbn/check', 'BroadbandPlanController@getNbnData');
            $router->get('broadband/connections', 'BroadbandPlanController@getConnectionType');
            $router->post('provider/list', 'BroadbandPlanController@getProviderList');
            $router->group(['prefix' => 'broadband/plan'], function () use ($router) {
                $router->post('list',  ['uses' => 'BroadbandPlanController@getPlans']);
            });
        });


        $router->group(['namespace' => 'ProviderManageSetting'], function () use ($router) {
            $router->get('provider/sections', ['uses' => 'ProviderManageSetting@providerSections']);
            $router->post('/applynow_content', ['uses' => 'ProviderManageSetting@applyNowContent']);
            $router->get('provider/permission','PermissionProvider@getPermission');
        });
        /** Search address **/
        $router->post('address/search', 'AddressController@searchAddress');
        /** Retrieve address **/
        $router->post('address/retrieve', 'AddressController@retrieveAddress');

        /** Get Distributor List w.r.t Postcode **/
        $router->get('energy/distributor', 'Distributor\DistributorController@getDistributorList');

        $router->group(['namespace' => 'General'], function () use ($router) {
            /** Get Term & Condition **/
            $router->get('term-condition', 'TermAndPrivacyController@getTermCondition');

            /** Get Privacy Policy **/
            $router->get('privacy-policy', 'TermAndPrivacyController@getPrivacyPolicy');

            /** Get Providers Terms Conditions **/
            $router->get('providers-terms-conditions', 'TermAndPrivacyController@providersTermsConditions');
        });
    });

    /** Post Back API for affiliate status **/
    $router->get('affiliate-sale-status', 'General\GeneralController@getAffiliateSaleStatus');
});
