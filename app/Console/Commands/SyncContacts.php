<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Contact;
use Exception;
use App\Traits\CustomLog;

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
            $client = new Client(['base_uri' => $this->apiUrl]);
            $hubspot = \HubSpot\Factory::createWithAccessToken($this->bearerToken, $client);
            $response = $hubspot->crm()->contacts()->basicApi()->getPage(10, null, ["phone", "firstname", "lastname", "date_of_birth", "mobilephone", "gender", "email", "hs_content_membership_status"], null, null, false);
            $count = 0;

            foreach ($response->getResults() as $val) {
                //print_r($val->getProperties());
                $contact = $val->getProperties();

                if ($contact['hs_content_membership_status'] == null || $contact['hs_content_membership_status'] == '') {
                    $contact['hs_content_membership_status'] = 'inactive';
                }
                $result = Contact::firstOrCreate(
                    ['email' => $contact['email']],
                    [
                        'first_name' => $contact['firstname'],
                        'last_name' => $contact['lastname'],
                        'phone_no' => $contact['phone'],
                        'mobile_no' => $contact['mobilephone'],
                        'date_of_birth' => $contact['date_of_birth'],
                        'gender' => $contact['gender'],
                        'contact_status_id' => $contact['hs_content_membership_status'] == 'active' ? 1 : 2,
                        'created_at' => \Carbon\Carbon::parse($contact['createdate'])->toDateTimeString(),
                        'updated_at' => \Carbon\Carbon::parse($contact['lastmodifieddate'])->toDateTimeString()
                    ]
                );
                // var_dump($result);
                // die;
                if (!$result->wasRecentlyCreated) {
                    $alreadyExists[] = $contact['email'];
                } else {
                    $count = $count + 1;
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
            return $this->respondInternalError();
        }

        /* Create a contact */
        // $contactInput = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInputForCreate();
        // $contactInput->setProperties([
        //     'email' => 'test@example.com'
        // ]);

        // $contact = $hubspot->crm()->contacts()->basicApi()->create($contactInput);
        // var_dump($contact);



        /* Update a contact */
        // $contactId = 301;
        // $newProperties = new \HubSpot\Client\Crm\Contacts\Model\SimplePublicObjectInput();
        // $newProperties->setProperties([
        //     'email' => 'updatedExample@example.com',
        //     'firstname' => 'Test First Name',
        //     'lastname' => 'Test Second Name',
        //     'date_of_birth' => '2010-01-02',
        //     'gender' => 'f',
        //     'phone' => '0123456789',
        //     'mobilephone' => '9876543210',
        //     'hs_content_membership_status' => 'inactive'
        // ]);

        // $hubspot->crm()->contacts()->basicApi()->update($contactId, $newProperties);

        /* Search a contact */
        // $search = "akumar@aeis.com";
        // $filter = new \HubSpot\Client\Crm\Contacts\Model\Filter();
        // $filter
        //     ->setOperator('EQ')
        //     ->setPropertyName('email')
        //     ->setValue($search);

        // $filterGroup = new \HubSpot\Client\Crm\Contacts\Model\FilterGroup();
        // $filterGroup->setFilters([$filter]);

        // $searchRequest = new \HubSpot\Client\Crm\Contacts\Model\PublicObjectSearchRequest();
        // $searchRequest->setFilterGroups([$filterGroup]);

        // // Get specific properties
        // $searchRequest->setProperties(['firstname', 'lastname', 'date_of_birth', 'gender', 'email', 'phone', 'mobilephone', 'status']);
        // $contactsPage = $hubspot->crm()->contacts()->searchApi()->doSearch($searchRequest);

        // echo $contactsPage;
    }
}
