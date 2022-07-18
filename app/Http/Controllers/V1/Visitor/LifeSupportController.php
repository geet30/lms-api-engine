<?php

namespace App\Http\Controllers\V1\Visitor;

use App\Http\Controllers\Controller;
use App\Models\LifeSupportEquipment;

class LifeSupportController extends Controller
{
    /**
     * Author: Sandeep Bangarh
     * Get life support content.
    */
    public function getLifeSupportContent()
    {
        try {
            $content = '<ol style="list-style-type: none;"><li style="text-align: justify;"><strong>What is classified as Life Support?</strong></li><li>According to the National Energy Retail Rules and the Victorian Energy Retail Code, equipment that classifies as being life support dependent, are:</li</ol><ul style="margin-left:10px"><li>  An oxygen concentrator;</li><li> An intermittent peritoneal dialysis machine;</li><li>  A kidney dialysis machine;</li><li>  A chronic positive airways pressure respirator(CPAP);</li><li> Crigler-Najjar syndrome phototherapy equipment;</li><li>A ventilator for life support;</li><li>  In relation to a particular customer;</li><li>  Any other equipment (whether fuelled by electricity or gas) that a registered medical practitioner certifies is required for a person residing at the customerÃ¢â‚¬â„¢s premises for life support or otherwise where the customer provides a current medical certificate certifying that a person residing at the customerÃ¢â‚¬â„¢s premises has a medical condition which requires continued supply of gas.</li></ul></p>';
            return successResponse('Content Found Successfully.', 2001, ['content'=> $content]);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine() . " in file:" . $e->getFile(), $e->getCode() ,2006, __FUNCTION__);
        }
    }

    /*
     * Author: Sandeep Bangarh
     * Get life support equipment.
    */
    public function getLifeSupportEquipment()
    {
        try {
            $lifeSupportTitles = LifeSupportEquipment::lifeSupportEquipments();
            $content = '<ol style="list-style-type: none;"><li style="text-align: justify;"><strong>What is classified as Life Support?</strong></li><li>According to the National Energy Retail Rules and the Victorian Energy Retail Code, equipment that classifies as being life support dependent, are:</li</ol><ul style="margin-left:10px"><li>  An oxygen concentrator;</li><li> An intermittent peritoneal dialysis machine;</li><li>  A kidney dialysis machine;</li><li>  A chronic positive airways pressure respirator(CPAP);</li><li> Crigler-Najjar syndrome phototherapy equipment;</li><li>A ventilator for life support;</li><li>  In relation to a particular customer;</li><li>  Any other equipment (whether fuelled by electricity or gas) that a registered medical practitioner certifies is required for a person residing at the customerÃ¢â‚¬â„¢s premises for life support or otherwise where the customer provides a current medical certificate certifying that a person residing at the customerÃ¢â‚¬â„¢s premises has a medical condition which requires continued supply of gas.</li></ul></p>';
            if (!empty($lifeSupportTitles)) {
                return successResponse('Please check list of all life support equipments.', 2001, ['life_support_titles'=> $lifeSupportTitles, 'content'=> $content]);
            }

            return successResponse('No result found', 2001);
        } catch (\Exception $e) {
            return errorResponse($e->getMessage() . " on line:" . $e->getLine() . " in file:" . $e->getFile(), $e->getCode() ,2006, __FUNCTION__);
        }
    }
}
