<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use DB;

class SyncCompanyTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotspot:syncCompanyTypes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will pull all company types from hubspot';

    private $apiUrl, $bearerToken;

    public function __construct()
    {
        parent::__construct();
        $this->apiUrl = env("HUBSPOT_API_BASEPATH") . 'companies';
        $this->bearerToken = env("HUBSPOT_API_TOKEN");
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $client = new Client(['base_uri' => $this->apiUrl]);
        $hubspot = \HubSpot\Factory::createWithAccessToken($this->bearerToken, $client);
        $response = $hubspot->apiRequest([
            'path' => '/crm/v3/properties/company/type',
        ]);

        $count = 0;
        $data = json_decode($response->getBody(), true);
        DB::table('account_types')->truncate();
        foreach ($data['options'] as $val) {
            DB::table('account_types')->insert(
                [
                    'account_type' => trim($val['label']),
                    'account_desc' => trim($val['value']),
                    'created_at' => \Carbon\Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon\Carbon::now()->toDateTimeString()
                ]
            );
            $count = $count + 1;
        }
        echo "$count records inserted";
    }
}
