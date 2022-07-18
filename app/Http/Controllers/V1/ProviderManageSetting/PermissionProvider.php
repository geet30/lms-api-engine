<?php
namespace App\Http\Controllers\V1\ProviderManageSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\ProviderPermission;


class PermissionProvider extends Controller
{
    public function getPermission(Request $request){
      try{
         return ProviderPermission::getPermission($request);
      }catch (\Exception $e) {
        return errorResponse($e->getMessage(), $e->getLine(), HTTP_STATUS_SERVER_ERROR, PLANLIST_ERROR_CODE, __FUNCTION__);
      }
    }
}