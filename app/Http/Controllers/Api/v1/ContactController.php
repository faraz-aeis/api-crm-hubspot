<?php

/**
 * Contact Controller
 *
 * @category Controller
 * @author   Faraz Khan <fkhan@aeis.com>
 * Date: 30-01-2024
 */

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\CustomLog;
use Exception;
use App\Models\Contact;

class ContactController extends Controller
{
    use CustomLog;
    private $contact;
    public function __construct()
    {
        $this->contact = new Contact();
    }
    public function index(Request $request)
    {
        try {
            $createRecord = array_search('contact.creation', array_column($request->all(), 'subscriptionType'));
            $deleteRecord = array_search('contact.deletion', array_column($request->all(), 'subscriptionType'));
            if (is_numeric($createRecord)) {
                $data = [];
                foreach ($request->all() as $contact) {
                    if ($contact['subscriptionType'] == 'contact.propertyChange') {
                        $propertyName = $contact['propertyName'];
                        $data['hubspot_id'] = $contact['objectId'];
                        switch ($propertyName) {
                            case "email":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'email')
                                    $data['email'] = $contact['propertyValue'];
                                break;
                            case "firstname":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'firstname')
                                    $data['first_name'] = $contact['propertyValue'];
                                break;
                            case "lastname":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'lastname')
                                    $data['last_name'] = $contact['propertyValue'];
                                break;
                            case "phone":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'phone')
                                    $data['phone_no'] = $contact['propertyValue'];
                                break;
                            case "mobilephone":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'mobilephone')
                                    $data['mobile_no'] = $contact['propertyValue'];
                                break;
                            case "date_of_birth":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'date_of_birth')
                                    $data['date_of_birth'] = \Carbon\Carbon::createFromTimestampMs($contact['propertyValue'], 'Asia/Kolkata')->toDateString();
                                break;
                            case "gender":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'gender')
                                    $data['gender'] = $contact['propertyValue'];
                                break;
                            case "hs_content_membership_status":
                                if (isset($contact['propertyName']) && $contact['propertyName'] == 'hs_content_membership_status')
                                    $data['contact_status_id'] = $contact['propertyValue'] == 'active' ? 1 : 2;
                                break;
                            default:
                                $this->infoLog('webhookHubspot', __FILE__, __LINE__, 'Unknown Property');
                        }
                    }
                }

                if (!empty($data)) {
                    $contact_status_id = isset($data['contact_status_id']) ? $data['contact_status_id'] : null;
                    if ($contact_status_id != null) {
                        $contact_status_id = $contact_status_id == 'active' ? 1 : 2;
                    }
                    $result = Contact::firstOrCreate(
                        ['email' => $data['email']],
                        [
                            'first_name' => isset($data['first_name']) ? $data['first_name'] : '',
                            'last_name' => isset($data['last_name']) ? $data['last_name'] : null,
                            'phone_no' => isset($data['phone_no']) ? $data['phone_no'] : null,
                            'mobile_no' => isset($data['mobile_no']) ? $data['mobile_no'] : null,
                            'date_of_birth' => isset($data['date_of_birth']) ? $data['date_of_birth'] : null,
                            'gender' => isset($data['gender']) ? $data['gender'] : null,
                            'contact_status_id' => $contact_status_id,
                            'hubspot_id' => isset($data['hubspot_id']) ? $data['hubspot_id'] : null
                        ]
                    );
                    if (!$result->wasRecentlyCreated) {
                        $alreadyExists[] = $data['email'];
                    }

                    if (isset($alreadyExists)) {
                        $msg = "Records already exists for " . $data['first_name'] . " " . $data['last_name'] . ".";
                        $this->infoLog('webhookHubspot', __FILE__, __LINE__, $msg);
                    } else {
                        $msg = "New records added with name: " . $data['first_name'] . " " . $data['last_name'] . ".";
                        $this->infoLog('webhookHubspot', __FILE__, __LINE__, $msg);
                    }
                } else {
                    $this->infoLog('webhookHubspot', __FILE__, __LINE__, 'Record could not be added.');
                }
            } else if (is_numeric($deleteRecord)) {
                foreach ($request->all() as $contact) {
                    $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                    if ($res) {
                        $contactName = $res->first_name . ' ' . $res->last_name;
                        $res->delete();
                        $msg = "$contactName has been deleted.";
                    } else {
                        $msg = "Record not found for deletion.";
                    }
                    $this->infoLog('webhookHubspot', __FILE__, __LINE__, $msg);
                }
            } else {
                foreach ($request->all() as $contact) {
                    $propertyName = $contact['propertyName'];
                    switch ($propertyName) {
                        case "email":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['email' => $contact['propertyValue']]);
                            break;
                        case "firstname":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['first_name' => $contact['propertyValue']]);
                            break;
                        case "lastname":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['last_name' => $contact['propertyValue']]);
                            break;
                        case "phone":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['phone_no' => $contact['propertyValue']]);
                            break;
                        case "mobilephone":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['mobile_no' => $contact['propertyValue']]);
                            break;
                        case "date_of_birth":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['date_of_birth' => \Carbon\Carbon::createFromTimestampMs($contact['propertyValue'], 'Asia/Kolkata')->toDateString()]);
                            break;
                        case "gender":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['gender' => $contact['propertyValue']]);
                            break;
                        case "hs_content_membership_status":
                            $res = Contact::where('hubspot_id', $contact['objectId'])->first();
                            $contactName = $res->first_name . ' ' . $res->last_name;
                            $res->update(['contact_status_id' => $contact['propertyValue'] == 'active' ? 1 : 2]);
                            break;
                        default:
                            $this->infoLog('webhookHubspot', __FILE__, __LINE__, 'Unknown Property');
                    }
                    $msg = $contact['propertyName'] . " of $contactName updated to " . $contact['propertyValue'];
                    $this->infoLog('webhookHubspot', __FILE__, __LINE__, $msg);
                }
            }
        } catch (Exception $exception) {
            $this->debugLog('webhookHubspot', __FILE__, __LINE__, $exception);
            //return $this->respondInternalError();
        }

    }
}
