<?php

namespace App\Repositories\Energy;

use DB;
use Storage;
use App\Models\Provider;
use Illuminate\Validation\Rule;
use Session;
use Auth;
Class SetDmoContent
{
     static function setContent($dmoData,$requestData){
         $masterdmoAttributs = $dmoData['masterDmoAttributes'];
         $planDmoAttributs = $dmoData['planDmoAttributes'];
            foreach($dmoData['planData'] as $key => $data){
                dd("sadad");

            }

        }

  
}
