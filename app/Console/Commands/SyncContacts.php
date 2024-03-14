<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Contact;
use Exception;
use App\Traits\CustomLog;
use DB;

class SyncContacts extends Command
{
    use CustomLog;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hotspot:syncContacts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will pull all contacts from hubspot';

    /**
     * Execute the console command.
     */

    private $apiUrl, $bearerToken;

    public function __construct()
    {
        parent::__construct();
        $this->apiUrl = env("HUBSPOT_API_BASEPATH") . 'contacts';
        $this->bearerToken = env("HUBSPOT_API_TOKEN");
    }
    public function handle()
    {
        try {
            $this->infoLog('syncContacts', __FILE__, __LINE__, "Sync Contacts From HubSpot Started");
            $client = new Client(['base_uri' => $this->apiUrl . '/search']);
            $hubspot = \HubSpot\Factory::createWithAccessToken($this->bearerToken, $client);
            $searchRequest = new \HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest();
            $contactsPage = $hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);
            $totalContacts = $contactsPage->getTotal();
            $numOfRecordsPerPage = 100;
            $loopIterations = floor($totalContacts / $numOfRecordsPerPage);
            $i = 0;
            $after = null;
            $count = 0;
            $client = new Client(['base_uri' => $this->apiUrl]);
            DB::table('contacts')->truncate();
            for ($i = 0; $i <= $loopIterations; $i++) {
                $hubspot = \HubSpot\Factory::createWithAccessToken($this->bearerToken, $client);
                $response = $hubspot->crm()->contacts()->basicApi()->getPage($numOfRecordsPerPage, $after, ["phone", "firstname", "lastname", "date_of_birth", "mobilephone", "gender", "email", "hs_content_membership_status", "hs_object_id"], null, null, false);
                if ($response->getPaging()) {
                    $after = $response->getPaging()->getNext()->getAfter();
                }
                foreach ($response->getResults() as $val) {
                    $contact = $val->getProperties();
                    if ($contact['email'] && ($contact['firstname'] || $contact['lastname'])) {
                        if ($contact['hs_content_membership_status'] == null || $contact['hs_content_membership_status'] == '') {
                            $contact['hs_content_membership_status'] = 'inactive';
                        }

                        if ($contact['gender'] != null) {
                            $gender = $contact['gender'] == 'Male' ? 'm' : 'f';
                        } else {
                            $gender = 'o';
                        }

                        $result = Contact::firstOrCreate(
                            ['email' => $contact['email']],
                            [
                                'first_name' => $contact['firstname'] ?? $contact['lastname'],
                                'last_name' => $contact['lastname'] ?? null,
                                'phone_no' => $contact['phone'] ?? null,
                                'mobile_no' => $contact['mobilephone'] ?? null,
                                'date_of_birth' => $contact['date_of_birth'] ?? null,
                                'gender' => $gender,
                                'contact_status_id' => $contact['hs_content_membership_status'] == 'active' ? 1 : 2,
                                'hubspot_id' => $contact['hs_object_id'],
                                'contact_type_id' => 2,
                                'created_at' => \Carbon\Carbon::parse($contact['createdate'])->toDateTimeString(),
                                'updated_at' => \Carbon\Carbon::parse($contact['lastmodifieddate'])->toDateTimeString()
                            ]
                        );
                        if (!$result->wasRecentlyCreated) {
                            $alreadyExists[] = $contact['email'];
                        } else {
                            $count = $count + 1;
                        }
                    }

                }
            }

            if (isset($alreadyExists)) {
                $msg = "These records already exists : " . implode(", ", $alreadyExists) . ". $count new records added.";
                echo $msg;
                $this->infoLog('syncContacts', __FILE__, __LINE__, $msg);
            } else {
                $msg = "$count new records added.";
                echo $msg;
                $this->infoLog('syncContacts', __FILE__, __LINE__, $msg);
            }
        } catch (Exception $exception) {
            $this->debugLog('syncContacts', __FILE__, __LINE__, $exception);
        }
    }
}
