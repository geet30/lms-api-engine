<?php

namespace App\Traits\LambdaTokenEx;

use Illuminate\Support\Facades\DB;
use App\Models\{Lead,VisitorBankInfo,VisitorDebitInfo};

trait LambdaTokenEx
{
    /**
     * Date: (11-March-2022)
     * set guzzle header data
     */
    public static function saveTokenEx($request)
    {
        try {   
            $lead_id = decryptGdprData($request->visit_id);
            $service_id = $request->header('serviceId');
            $provider_id = 0;
            if($service_id == 1){ //energy
                $provider_id = DB::table('sale_products_energy')->where('lead_id', $lead_id)->where('service_id', $service_id)->pluck('provider_id')->first();
            } elseif($service_id == 2) { //mobile
                $provider_id = DB::table('sale_products_mobile')->where('lead_id', $lead_id)->where('service_id', $service_id)->pluck('provider_id')->first();
            } elseif($service_id == 3){//broadband
                $provider_id = DB::table('sale_products_broadband')->where('lead_id', $lead_id)->where('service_id', $service_id)->pluck('provider_id')->first();
            }
    
            $insertdata = [];
            if(isset($request->type) && $request->type == 1){
                $insertdata['first_six'] = $request->first_six ? setTokenexEncryptData($request->first_six) : '';
                $insertdata['last_four'] = $request->last_four ? setTokenexEncryptData($request->last_four) : '';
                if($request->name_on_card !=""){
                    $nameOfCard = stripslashes($request->name_on_card);
                }
                $insertdata['name_on_card'] = $request->name_on_card ? setTokenexEncryptData($nameOfCard) : '';
                $insertdata['exp_month'] = $request->exp_month ? setTokenexEncryptData($request->exp_month) : '';
                $insertdata['exp_year'] = $request->exp_year ? setTokenexEncryptData($request->exp_year) : '';
                $insertdata['cvv'] = $request->cvv ? setTokenexEncryptData($request->cvv) : '';
                $insertdata['card_type'] = $request->card_type ? setTokenexEncryptData($request->card_type) : '';
                $insertdata['reference_number'] = $request->reference_number ? setTokenexEncryptData($request->reference_number) : '';
                $insertdata['token'] = $request->token ? setTokenexEncryptData($request->token) : '';
                $insertdata['token_hMAC'] = $request->token_hMAC ? setTokenexEncryptData($request->token_hMAC) : '';
                $insertdata['is_valid'] = $request->is_valid ? $request->is_valid : '';
                $insertdata['is_cvv_valid'] = $request->is_cvv_valid ? $request->is_cvv_valid : '';
                $insertdata['cvv_included'] = $request->cvv_included ? $request->cvv_included : '';
                $insertdata['tokenize_data'] = $request->tokenize_data ? $request->tokenize_data : '';
                
                $insertdata['type'] = $request->type ? $request->type : '';  //type for or credit/debit or bank(1,2)
                $insertdata['service_type'] = $request->header('ServiceId') ? $request->header('ServiceId') : '';
                $insertdata['lead_id'] = $lead_id;  //use visitor user id get from sale table
                $insertdata['provider_id'] = $provider_id ? $provider_id : 0;
                $insertdata['auth_key_used'] = $request->auth_key_used ? setTokenexEncryptData($request->auth_key_used) : '';
                $insertdata['timestamp_used'] = $request->timestamp_used ? $request->timestamp_used : '';
                VisitorDebitInfo::updateOrCreate(['lead_id' => $lead_id, 'type' => $request->type], $insertdata);
               
            } else if(isset($request->type) && $request->type == 2){
                $insertdata['lead_id'] = $lead_id;  //use visitor user id get from sale table                 
                $insertdata['bank_name'] = $request->bank_name ? encryptBankDetail($request->bank_name) : '';
                $insertdata['branch_name'] = $request->branch_name ? encryptBankDetail($request->branch_name) : '';
                $insertdata['name_on_account'] = $request->name_on_account ? encryptBankDetail($request->name_on_account) : '';
                $insertdata['account_number'] = $request->account_no ? encryptBankDetail($request->account_no) : '';
                $insertdata['bsb'] = $request->bsb_no ? encryptBankDetail($request->bsb_no) : '';
                $insertdata['provider_id'] = $provider_id ? $provider_id : 0;
                VisitorBankInfo::updateOrCreate(['lead_id' => $lead_id], $insertdata);
            }
            Lead::where('lead_id',$lead_id)->update(['visitor_debit_type'=>$request->type]);
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

   

}
