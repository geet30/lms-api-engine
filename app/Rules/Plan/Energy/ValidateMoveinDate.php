<?php

namespace App\Rules\Plan\Energy;

use Illuminate\Contracts\Validation\Rule;

use App\Models\MoveInCalender;
use Carbon\Carbon;

class ValidateMoveinDate implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    protected $requestData = [];
    public function __construct($request)
    {
        $this->requestData = $request;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $request = $this->requestData;

        $state = explode(',', $request->post_code);
        $state = trim($state['2']);
        $holidays = MoveInCalender::where('holiday_type', 'national')
            ->orWhere(function ($q) use ($state) {
                $q->where('holiday_type', 'state')
                    ->where('state', trim($state));
            })
            ->pluck('date');
        $movingDate = explode('/', $value);
        $dateFormat = $movingDate[2] . '-' . $movingDate[1] . '-' . $movingDate[0];

        if (in_array($dateFormat, $holidays->toArray())) {

            return false;
        }

        // check if moving date is weekend or not start here.
        $movingDatePassed = $movingDate[1] . '/' . $movingDate[0] . '/' . $movingDate[2];
        if (date('N', strtotime($movingDatePassed)) >= 6) {
            return false;
            // check if moving date is weekend or not end here.
        } else {
            $dt = Carbon::now()->addDay();
            $today = Carbon::now();
            //create a carbon instance of selected moving date
            $dt2 = Carbon::create($movingDate[2], $movingDate[1], $movingDate[0])->addDay();
            $todayDiff = $today->diffInDays($dt2, false);
            //get working days according to the selected date.
            $movenDays = $dt->diffInDaysFiltered(function (Carbon $date) use ($todayDiff) {
                if (!$date->isWeekend() && $todayDiff >= 0)
                    return $date;
            }, $dt2);
            if ($movenDays == 0) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Please enter valid Moving date.';
    }
}
