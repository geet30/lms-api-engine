<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Firewall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firewall:whitelist {ipOrCountry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'By this command we can add any IP or country in out white list';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ipOrCountry = $this->argument('ipOrCountry');
        $currentDate = Carbon::now()->toDateTimeString();
        $ipOrCountry = strtolower(trim($ipOrCountry));
        if ($ipOrCountry && $this->validCountryOrIp($ipOrCountry)) {
            $countryExist = DB::table('firewall')->where('ip_address', $ipOrCountry)->exists();
            if ($countryExist) {
                DB::table('firewall')->where('ip_address', $ipOrCountry)->update(['whitelisted' => 1, 'updated_at' => $currentDate]);
            } else {
                DB::table('firewall')->insert(['ip_address' => $ipOrCountry, 'whitelisted' => 1, 'created_at' => $currentDate, 'updated_at' => $currentDate]);
            }
            $this->info('Ip or Country added to whitelist');
        } else {
            $this->info('Ip or Country code is invalid, if you are trying to add country then please use "country:" prefix before country code, for country code you can refer this link: http://www.spoonfork.org/isocodes.html');
        }
    }

    /**
     * Validate Country and IP.
     * Author: Sandeep Bangarh
     * @param  string  $countryOrIp
     * @return boolean
     */
    private function validCountryOrIp($countryOrIp)
    {
        $countryOrIp = strtolower($countryOrIp);

        if (filter_var($countryOrIp, FILTER_VALIDATE_IP)) {
            return true;
        }

        $countries = @json_decode(file_get_contents(public_path('geoip/countries.json')), true);
        $countryCodes = array_column($countries, 'code');
        if (Str::startsWith($countryOrIp, 'country:') && in_array(strtoupper(str_replace("country:", "", $countryOrIp)), $countryCodes)) {
            return true;
        }

        return false;
    }
}
