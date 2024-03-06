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
            $response = $hubspot->crm()->contacts()->basicApi()->getPage(10, null, ["phone", "firstname", "lastname", "date_of_birth", "mobilephone", "gender", "email", "hs_content_membership_status", "hs_object_id"], null, null, false);
            $count = 0;

            foreach ($response->getResults() as $val) {
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
