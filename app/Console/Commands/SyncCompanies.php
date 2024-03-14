<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Account;
use Exception;
use App\Traits\CustomLog;
use DB;

class SyncCompanies extends Command
{
    use CustomLog;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotspot:syncCompanies';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will pull all companies from hubspot';

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
        try {
            $this->infoLog('syncAccounts', __FILE__, __LINE__, "Sync Accounts From HubSpot Started");
            $client = new Client(['base_uri' => $this->apiUrl . '/search']);
            $hubspot = \HubSpot\Factory::createWithAccessToken($this->bearerToken, $client);
            $searchRequest = new \HubSpot\Client\Crm\Companies\Model\PublicObjectSearchRequest();
            $companiesPage = $hubspot->crm()->companies()->searchApi()->doSearch($searchRequest);
            $totalCompanies = $companiesPage->getTotal();
            $numOfRecordsPerPage = 100;
            $loopIterations = floor($totalCompanies / $numOfRecordsPerPage);
            $i = 0;
            $after = null;
            $count = 0;
            $client = new Client(['base_uri' => $this->apiUrl]);
            DB::table('accounts')->truncate();
            for ($i = 0; $i <= $loopIterations; $i++) {
                $hubspot = \HubSpot\Factory::createWithAccessToken($this->bearerToken, $client);
                $response = $hubspot->crm()->companies()->basicApi()->getPage($numOfRecordsPerPage, $after, ["industry", "name", "domain", "owneremail", "phone", "description", "founded_year", "address", "address2", "city", "zip", "state", "hs_created_by_user_id", "hs_updated_by_user_id", "hs_avatar_filemanager_key", "timezone", "numberofemployees", "linkedin_company_page", "type"], null, null, false);
                if ($response->getPaging()) {
                    $after = $response->getPaging()->getNext()->getAfter();
                }
                foreach ($response->getResults() as $val) {
                    $account = $val->getProperties();
                    $createdAt = $val->getCreatedAt();
                    $updatedAt = $val->getUpdatedAt();

                    if (isset($account['industry']) && ($account['industry'] != null || $account['industry'] != '')) {
                        $industry = DB::table('industries')->where('industry_desc', 'like', '%' . $account['industry'] . '%')->first();
                        if ($industry) {
                            $industry_id = $industry->industry_id;
                        } else {
                            $industry_id = null;
                        }
                    } else {
                        $industry_id = null;
                    }

                    if (isset($account['type']) && ($account['type'] != null || $account['type'] != '')) {
                        $account_type = DB::table('account_types')->where('account_desc', 'like', '%' . $account['type'] . '%')->first();

                        if ($account_type) {
                            $account_type_id = $account_type->account_type_id;
                        } else {
                            $account_type_id = null;
                        }
                    } else {
                        $account_type_id = null;
                    }

                    if (isset($account['state']) && ($account['state'] != null || $account['state'] != '')) {
                        $state = DB::table('states')->where('state_name', 'like', '%' . $account['state'] . '%')->first();
                        if ($state) {
                            $state_id = $state->state_id;
                        } else {
                            $state_id = null;
                        }
                    } else {
                        $state_id = null;
                    }

                    if (isset($account['founded_year']) && ($account['founded_year'] != null || $account['founded_year'] != '')) {
                        $founded_year = $account['founded_year'] . "-01-01";
                        $founded_year = \Carbon\Carbon::parse($founded_year)->format('Y');
                    } else {
                        $founded_year = null;
                    }
                    $insertData = [
                        'industry_id' => $industry_id,
                        'account_type_id' => $account_type_id,
                        'account_name' => $account['name'] ?? null,
                        'email' => $account['owneremail'] ?? null,
                        'account_desc' => $account['description'] ?? null,
                        'website' => $account['domain'] ?? null,
                        'phone_no' => trim($account['phone'] ?? '') != '' ? $account['phone'] : null,
                        'year_of_estb' => $founded_year,
                        'address_1' => $account['address'] ?? null,
                        'address_2' => $account['address2'] ?? null,
                        'city' => $account['city'] ?? null,
                        'postal_code' => $account['zip'] ?? null,
                        'state_id' => $state_id,
                        'no_of_employees' => intval($account['numberofemployees'] ?? 0),
                        'time_zone' => $account['timezone'] ?? null,
                        'linkedin_page_url' => $account['linkedin_company_page'] ?? null,
                        'hubspot_id' => $account['hs_object_id'] ?? null,
                        'account_status_id' => 1,
                        'created_at' => \Carbon\Carbon::parse($createdAt)->toDateTimeString(),
                        'updated_at' => \Carbon\Carbon::parse($updatedAt)->toDateTimeString()
                    ];
                    Account::create($insertData);
                    $count = $count + 1;
                }
            }
            $msg = "$count new records added.";
            echo $msg;
            $this->infoLog('syncAccounts', __FILE__, __LINE__, $msg);
        } catch (Exception $exception) {
            $this->debugLog('syncAccounts', __FILE__, __LINE__, $exception);
        }
    }
}
