<?php

namespace App\Http\Controllers;

use App\IosDevice;
use App\IosDeviceRegistration;
use App\Jobs\PushLoyaltyCard;
use App\Passes;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Http\Request;
use Mockery\Generator\StringManipulation\Pass\Pass;
use Passbook\Pass\Field;
use Passbook\Pass\Image;
use Passbook\PassFactory;
use Passbook\Pass\Barcode;
use Passbook\Pass\Structure;
use Passbook\Type\Coupon;
use Passbook\Type\EventTicket;
use Mailgun\Mailgun;
use Jenssegers\Agent\Agent;
use PKPass\PKPass;
use App\Jobs\SendMail;
use App\Log;
use App\Http\CRM;
use Illuminate\Support\Facades\DB;

use App\MailData;

class PassbookController extends Controller
{
    private $baseUrl = '';

    /**
     * PassbookController constructor.
     */
    public function __construct()
    {
        date_default_timezone_set("Asia/Hong_Kong");
        $this->baseUrl = url('/');
    }

    public function push_test()
    {
        $devices = IosDevice::all();

        foreach ($devices as $device) {
            \Log::info("Push Notification:" . $device->push_token);
            $this->push_notification($device->push_token, "");
        }
    }


    //Push by walletpasses api
    public function GCM_push_notification($push_token, $owningteam)
    {
        \Log::info("Function Push Notification of Android has been Execute");
        $apiKey = "b185e0e86229495eb2d36b61be31dabd";

        // Set POST variables
        $url = 'https://walletpasses.appspot.com/api/v1/push';

        $fields = array(
            'passTypeIdentifier' => "pass.com.idcrmltd.pushloyalty",
            'pushTokens' => array($push_token),
        );

        $headers = array(
            'Authorization: ' . $apiKey,
            'Content-Type: application/json'
        );

//        // Open connection
//        $ch = curl_init();
//
//        // Set the url, number of POST vars, POST data
//        curl_setopt( $ch, CURLOPT_URL, $url );
//
//        curl_setopt( $ch, CURLOPT_POST, true );
//        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
//        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
//
//        curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode($fields));

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);

        // Close connection
        curl_close($ch);

        \Log::info("Result:" . $result);
    }


    public function push_notification($push_token, $owningteam)
    {
        $deviceToken = "" . $push_token . "";

        \Log::info("Push Notification for owningteam:" . $owningteam);

  //      if ($owningteam == LOYALTY_PROGRAM_TEAM_LYN) {
            //$passphrase=When you generate ck.pem used inside
            $passphrase = P12_PASSWORD;

            //Ck.pem half path of server
            $ck_pem_path = PUSH_NOTIFICATION_CERT;
//        } else {
//            //$passphrase=When you generate ck.pem used inside
//            $passphrase = P12_PASSWORD_CLASSIFIED;
//
//            //Ck.pem half path of server
//            $ck_pem_path = PUSH_NOTIFICATION_CERT_CLASSIFIED;
//        }

        //When your application live then change development to production
        $development = "production";

        if ($development == "development") {
            $socket_url = "ssl://gateway.sandbox.push.apple.com:2195";
        } else {
            $socket_url = "ssl://gateway.push.apple.com:2195";
        }

//        $message_body = array(
//            'type' => 1,
//            'alert' => 'Test notification',
//            'badge' => 1,
//            'sound' => 'default'
//        );

        $ctx = stream_context_create();
        stream_context_set_option($ctx, 'ssl', 'local_cert', $ck_pem_path);
        stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
        // Open a connection to the APNS server
        $fp = stream_socket_client(
            $socket_url, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx
        );
        if (!$fp) {
            $error = "Failed to connect: $err $errstr" . PHP_EOL;
        }

        $body['aps'] = "";
        $payload = json_encode($body);
        // Build the binary notification
        $msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
        // Send it to the server
        $result = fwrite($fp, $msg, strlen($msg));
        fclose($fp);

        if (!$result) {
            $return = "Error, notification not sent" . PHP_EOL;
        } else {
            $return = "Success, notification sent";
        }
        \Log::info("Push Notification Result:" . $return);
        return response()->json(array(
            'message' => $return
        ));
    }

    public function apns($device_token)
    {
        PushNotification::app('appNameIOS')
            ->to($device_token)
            ->send('Hello World, i`m a push message');
    }

    /**
     * Registration
     * register a device to receive push notifications for a pass
     *
     * POST /v1/devices/<deviceID>/registrations/<typeID>/<serial#>
     * Header: Authorization: ApplePass <authenticationToken>
     * JSON payload: { "pushToken" : <push token, which the server needs to send push notifications to this device> }
     *
     * server action: if the authentication token is correct, associate the given push token and device identifier with this pass
     * server response:
     * --> if registration succeeded: 201
     * --> if this serial number was already registered for this device: 304
     * --> if not authorized: 401
     *
     * @param Request $request
     * @param $device_id
     * @param $pass_type_id
     * @param $serial_number
     * @return \Illuminate\Http\JsonResponse
     */
    public function register_pass(Request $request, $device_id, $pass_type_id, $serial_number)
    {
        $authentication_token = $_SERVER['HTTP_AUTHORIZATION'];
        $device_type = explode(" ", $authentication_token)[0];

        // For iOS
        $authentication_token = str_replace('ApplePass ', '', $authentication_token);

        //For Android
        $authentication_token = str_replace('AndroidPass ', '', $authentication_token);


        \Log::info('Handling registration request...');
        \Log::info('RegistrationRequest');
        \Log::info('device_id:' . $device_id);
        \Log::info('pass_type_id:' . $pass_type_id);
        \Log::info('serial_number:' . $serial_number);
        \Log::info('authentication_token:' . $authentication_token);
        \Log::info('push_token:' . $request->pushToken);
        \Log::info('Device Type:' . $device_type);
        $passes =Passes::where('serial_number', $serial_number)->where('authentication_token', $authentication_token)->first();

        if ($passes) {
            \Log::info('Pass and authentication token match.');

            # Validate that the device has not previously registered
            # Note: this is done with a composite key that is combination of the device_id and the pass serial_number
            $uuid = $device_id . '-' . $serial_number;
            if (IosDeviceRegistration::where('uuid', $uuid)->count() < 1) {
                # No registration found, lets add the device

                $registration = new IosDeviceRegistration();
                $registration->uuid = $uuid;
                $registration->device_id = $device_id;
                $registration->pass_type_id = $pass_type_id;
                $registration->serial_number = $serial_number;
                $registration->device_type = $device_type;
                $registration->save();

                $device = new IosDevice();
                $device->device_id = $device_id;
                $device->push_token = $request->pushToken;
                $device->save();

                $pusback = array(
                    "idcrm_devicelibraryid" => $device->device_id,
                    "idcrm_pushtoken" => $device->push_token,
                    "idcrm_lastuseddate" => time() + date("HKT")
                );

                $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, $registration->serial_number, "idcrm_loyaltycard", $pusback));

                # Return a 201 CREATED status
                return response()->json(array(
                    'status' => 201
                ));


            } else {
                # The device has already registered for updates on this pass
                # Acknowledge the request with a 200 OK response
                return response()->json(array(
                    'status' => 200
                ));
            }

        } else {
            # The device did not statisfy the authentication requirements
            # Return a 401 NOT AUTHORIZED response
            return response()->json(array(
                'status' => 401
            ));
        }
    }

    /**
     * Logging/Debugging from the device
     *
     * log an error or unexpected server behavior, to help with server debugging
     * POST /v1/log
     * JSON payload: { "description" : <human-readable description of error> }
     * server response: 200
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function log(Request $request)
    {
        \Log::info($request);
        return response()->json(array(
            'status' => 200
        ));
    }

    /**
     * Pass delivery
     *
     * GET /v1/passes/<typeID>/<serial#>
     * Header: Authorization: ApplePass <authenticationToken>
     *
     * server response:
     * --> if auth token is correct: 200, with pass data payload
     * --> if auth token is incorrect: 401
     *
     * @param $pass_type_id
     * @param $serial_number
     * @return \Illuminate\Http\JsonResponse
     */

    public function deliver_pass($pass_type_id, $serial_number)
    {
        \Log::info('Handling pass delivery request...');

        $authentication_token = $_SERVER['HTTP_AUTHORIZATION'];
        $authentication_token = str_replace('ApplePass ', '', $authentication_token);

        $pass = Passes::where('serial_number', $serial_number)
            ->where('pass_type_id', $pass_type_id)
            ->where('authentication_token', $authentication_token)
            ->first();

        if (count($pass) > 0) {
            $loyaltyData = array();

            $loyaltyData['cardId'] = $pass->card_id;
            $loyaltyData['firstname'] = $pass->first_name;
            $loyaltyData['lastname'] = $pass->last_name;
            $loyaltyData['contact_image'] = $pass->thumbnail;
            $loyaltyData['createdon'] = $pass->created_on;
            $loyaltyData['emailaddress1'] = $pass->email;
            $loyaltyData['mobilephone'] = $pass->phone;
            $loyaltyData['venue_name'] = $pass->venue_name;
            $loyaltyData['idcrm_programname'] = $pass->loyalty_program;
            $loyaltyData['idcrm_totalpoints'] = $pass->total_points;
            $loyaltyData['serial_number'] = $pass->serial_number;
            $loyaltyData['authenticationToken'] = $pass->authentication_token;
            $loyaltyData['date_of_birth'] = $pass->date_of_birth;
            $loyaltyData['owningteam'] = $pass->owningteam;
            $loyaltyData['action'] = "update";

            //Immediately render pass
            $this->generate_pass_gcrc($loyaltyData);

            //$this->generate_card($loyaltyData);
        } else {
            return response()->json(array(
                'status' => 401
            ));
        }
    }

    /**
     * Unregister
     *
     * unregister a device to receive push notifications for a pass
     * DELETE /v1/devices/<deviceID>/registrations/<passTypeID>/<serial#>
     * Header: Authorization: ApplePass <authenticationToken>
     * server action: if the authentication token is correct, disassociate the device from this pass
     * server response:
     * --> if disassociation succeeded: 200
     * --> if not authorized: 401
     *
     * @param $device_id
     * @param $pass_type_id
     * @param $serial_number
     * @return \Illuminate\Http\JsonResponse
     */
    public function unregister_pass($device_id, $pass_type_id, $serial_number)
    {
        \Log::info('Handling unregistration request...');

        $authentication_token = $_SERVER['HTTP_AUTHORIZATION'];
        $authentication_token = str_replace('ApplePass ', '', $authentication_token);

        $registered_passes = Passes::where('serial_number', $serial_number)->where('authentication_token', $authentication_token)->count();

        if ($registered_passes > 0) {
            # Validate that the device has previously registered
            # Note: this is done with a composite key that is combination of the device_id and the pass serial_number
            $uuid = $device_id . '-' . $serial_number;
            if (IosDeviceRegistration::where('uuid', $uuid)->count() > 0) {
                IosDeviceRegistration::where('uuid', $uuid)->delete();
                return response()->json(array(
                    'status' => 200
                ));
            } else {
                \Log::info('Registration does not exist.');
                return response()->json(array(
                    'status' => 401
                ));
            }
        } else {
            # Not authorized
            return response()->json(array(
                'status' => 401
            ));
        }
    }

    /**
     * Updatable passes
     *
     * get all serial #s associated with a device for passes that need an update
     * Optionally with a query limiter to scope the last update since
     *
     * GET /v1/devices/<deviceID>/registrations/<typeID>
     * GET /v1/devices/<deviceID>/registrations/<typeID>?passesUpdatedSince=<tag>
     *
     * server action: figure out which passes associated with this device have been modified since the supplied tag (if no tag provided, all associated serial #s)
     * server response:
     * --> if there are matching passes: 200, with JSON payload: { "lastUpdated" : <new tag>, "serialNumbers" : [ <array of serial #s> ] }
     * --> if there are no matching passes: 204
     * --> if unknown device identifier: 404
     *
     * @param $device_id
     * @param $pass_type_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_pass($device_id, $pass_type_id)
    {
        // Check first that the device has registered with the service

        \Log::info("Handling updates request...");

        if (IosDeviceRegistration::where('device_id', $device_id)->count() > 0) {
            # Find the registrations for the device
            $registered_serial_numbers = IosDeviceRegistration::where('device_id', $device_id)
                ->where('pass_type_id', $pass_type_id)
                ->pluck('serial_number');

            $registered_passes = DB::table('passes')
                ->whereIn('serial_number', $registered_serial_numbers)
                ->get();

            # Are there passes that this device should recieve updates for?
            if (count($registered_passes) > 0) {
                # Found passes that could be updated for this device

                # Build the response object
                //$currentDate = date("Y-m-d h:i:sa");

                $update_time = date(DATE_RFC822);

                $updatable_passes_payload = [];
                $updatable_passes_payload['lastUpdated'] = $update_time;
                $updatable_passes_payload['serialNumbers'] = $registered_passes->pluck('serial_number')->all();

                return response()->json($updatable_passes_payload);

            } else {
                return response()->json(array(
                    'status' => 204
                ));
            }
        } else {
            # This device is not currently registered with the service
            //return status 404
            return response()->json(array(
                'status' => 404
            ));
        }
    }

    public function test_send_data_crm()
    {

        $pusback = array(
            "idcrm_authenticationtoken" => "123445444",
            "idcrm_barcode" => "42b4e544-8029-e711-8155-e0071b67cb31",
            "idcrm_passtypeid" => PASS_TYPE_VIAZUL_IDENTIFIER,
            "idcrm_lastuseddate" => gmdate("Y-m-d H:i:s"),
            "idcrm_serialnumber" => "1111111111",
            "idcrm_pushstatus" => 527210000
        );

        return $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, "dc3778ff-0f27-e711-8183-e0071b67bbe1", "idcrm_loyaltycard", $pusback));

    }


    public function generate_pass_gcrc($loyaltyData)
    {
        $image_path = resource_path('images');

        if (!file_exists(WWDR_FILE)) {
            dd("WWDRcertPath not exist");
        }

        if (!file_exists(P12_FILE)) {
            dd("Certificate not exist");
        }

        $pass = new PKPass();

        $pass->setCertificate(P12_FILE);
        $pass->setCertificatePassword(P12_PASSWORD);
        $pass->setWWDRcertPath(WWDR_FILE);

        $standardKeys = [
            'serialNumber' => $loyaltyData['serial_number'],
            'formatVersion' => 1,
            "description" => "The Paulistas Clube",
            'organizationName' => 'The Paulistas Clube',
            'passTypeIdentifier' => PASS_TYPE_IDENTIFIER,
            'teamIdentifier' => TEAM_IDENTIFIER,
            'webServiceURL' => WEB_SERVICE_URL,
            'authenticationToken' => $loyaltyData['authenticationToken']
        ];


        $full_name = isset($loyaltyData['firstname']) ? $loyaltyData['firstname'] : "";
        $full_name .= " ";
        $full_name .= isset($loyaltyData['lastname']) ? $loyaltyData['lastname'] : "";

        $associatedAppKeys = [];
        $relevanceKeys = [];
        $styleKeys = [
            'eventTicket' => [
                'headerFields' => [
                    [
                        'key' => 'current_points',
                        'label' => 'Current Points',
                        'textAlignment' => 'PKTextAlignmentRight',
                        'value' => isset($loyaltyData['idcrm_totalpoints']) ? intval($loyaltyData['idcrm_totalpoints']) : '',
                        'changeMessage' => 'Points changed to %@'
                    ]
                ],
                'primaryFields' => [
                    [
                        'key' => 'name',
                        'label' => 'Name',
                        'value' => $full_name,
                        'changeMessage' => "Changed to %@"
                    ]
                ],
                'secondaryFields' => [
                    [
                        'key' => 'email',
                        'label' => 'Email',
                        'value' => isset($loyaltyData['emailaddress1']) ? $loyaltyData['emailaddress1'] : "",
                        'changeMessage' => "Changed to %@"
                    ]
                ],
                'auxiliaryFields' => [
                    [
                        'key' => 'phone',
                        'label' => 'Phone',
                        'value' => isset($loyaltyData['mobilephone']) ? $loyaltyData['mobilephone'] : "",
                        'changeMessage' => "Changed to %@"
                    ],
                    [
                        'key' => 'dob',
                        'label' => 'Date of Birth',
                        'value' => !empty($loyaltyData['date_of_birth']) ? date("d.m.Y", strtotime($loyaltyData['date_of_birth'])) : "N/A",
                        'changeMessage' => "Changed to %@"
                    ],
                    [
                        'key' => 'since',
                        'label' => 'Member Since',
                        'textAlignment' => 'PKTextAlignmentRight',
                        'value' => isset($loyaltyData['createdon']) ? date("d.m.Y", strtotime($loyaltyData['createdon'])) : '',
                        'changeMessage' => "Changed to %@"
                    ]

                ],
                'backFields' => [
                    array(
                        'key' => 'terms',
                        'label' => 'TERMS & CONDITIONS',
                        'value' => '• Each person may only apply for one e-loyalty card. Members must be 18 years and over.
• Customers must present a valid Clube card to credit the transaction and visit count
• Transaction values (inclusive of service charge) will be rounded down
• Transaction values and the visit count are to be earned immediately upon settlement of the bill
• Transaction values and the visit count cannot be transferred to another party
• The Paulistas Clube card and associated offers cannot be exchanged for cash and may not be used in conjunction with any other offers and promotions. Click on Front Click on Back
• The e-loyalty card is valid in Hong Kong only.
• Uma Nota reserves the right to modify The Paulistas Clube structure, benefits and Terms & Conditions other without prior notice
• In case of dispute, the decision of Uma Nota’s management of The Paulistas Clube shall be final. The Paulistas Clube is trademarked by Uma Nota.',
                        'changeMessage' => "Changed to %@"
                    ),
                    [
                        'key' => 'passSourceSignature',
                        'label' => 'Powered by HARi crm',
                        'value' => 'For more information or to create your own passes, visit: http://www.haricrm.com
This pass may contain trademarks that are licensed or affiliated with HARi crm.'
                    ]
                ]
            ],

        ];
        $visualAppearanceKeys = [

            'backgroundColor' => 'rgb(255,255,255)',

            'foregroundColor' => 'rgb(188,155,93)',
            'labelColor' => 'rgb(51,51,51)',
            'barcode' => [
                'format' => 'PKBarcodeFormatQR',
                'messageEncoding' => 'iso-8859-1',
                'message' => isset($loyaltyData['serial_number']) ? $loyaltyData['serial_number'] : ""
            ],
        ];
        $webServiceKeys = [];

        $passData = array_merge(
            $standardKeys,
            $associatedAppKeys,
            $relevanceKeys,
            $styleKeys,
            $visualAppearanceKeys,
            $webServiceKeys
        );

        $pass->setJSON(json_encode($passData));

        // Add files to the PKPass package
        $pass->addFile($image_path . '/icon.png');
        $pass->addFile($image_path . '/icon@2x.png');
        $pass->addFile($image_path . '/logo.png');
        $pass->addFile($image_path . '/logo@2x.png');
        $pass->addFile($image_path . '/logo@3x.png');
//        $pass->addFile($image_path . '/background@2x.png');
        $pass->addFile(!empty($loyaltyData['contact_image']) ? $loyaltyData['contact_image'] : $image_path . '/thumbnail.png');
//        $pass->addFile($image_path.'/thumbnail@2x.png');
        if (!$pass->create(true)) { // Create and output the PKPass
            return 'Error: ' . $pass->getError();
        }
    }

    public function test_card($serial_number)
    {

        $pass = Passes::where('serial_number', $serial_number)->first();
        if (count($pass) > 0) {
            $loyaltyData = array();

            $loyaltyData['cardId'] = $pass->card_id;
            $loyaltyData['firstname'] = $pass->first_name;
            $loyaltyData['lastname'] = $pass->last_name;
            //$loyaltyData['contact_image'] = $pass->thumbnail;
            $loyaltyData['createdon'] = $pass->created_on;
            $loyaltyData['emailaddress1'] = $pass->email;
            $loyaltyData['mobilephone'] = $pass->phone;
            $loyaltyData['venue_name'] = $pass->venue_name;
            $loyaltyData['idcrm_programname'] = $pass->loyalty_program;
            $loyaltyData['idcrm_totalpoints'] = $pass->total_points;
            $loyaltyData['serial_number'] = $pass->serial_number;
            $loyaltyData['authenticationToken'] = $pass->authentication_token;
            $loyaltyData['date_of_birth'] = $pass->date_of_birth;
            $loyaltyData['owningteam'] = $pass->owningteam;

            //Immediately render pass
            $this->generate_pass_gcrc($loyaltyData);
        }
    }

    private function _store_card_data($action, $loyaltyData =[])
    {
        $result = null;

        if ($action == 'Update') {

            $passCard =Passes::where('serial_number', $loyaltyData['serial_number'])->get();
            if($passCard->count() >0)
            {
                $result = Passes::where('serial_number', $loyaltyData['serial_number'])->update([
                    'card_id' => isset($loyaltyData['cardId'])?$loyaltyData['cardId']:"",
                    'first_name' => isset($loyaltyData['firstname'])?$loyaltyData['firstname']:"",
                    'last_name' => isset($loyaltyData['lastname'])?$loyaltyData['lastname']:"",
                    'created_on' => isset($loyaltyData['createdon']) ? $loyaltyData['createdon'] : '',
                    'email' => isset($loyaltyData['emailaddress1']) ? $loyaltyData['emailaddress1'] : '',
                    'phone' => isset($loyaltyData['mobilephone']) ? $loyaltyData['mobilephone'] : '',
                    'venue_name' => isset($loyaltyData['venue_name']) ? $loyaltyData['venue_name'] : '',
                    'loyalty_program' => isset($loyaltyData['idcrm_programname']) ? $loyaltyData['idcrm_programname'] : '',
                    'total_points' => isset($loyaltyData['idcrm_totalpoints']) ? $loyaltyData['idcrm_totalpoints'] : '',
                    'authentication_token' => isset($loyaltyData['authenticationToken'])?$loyaltyData['authenticationToken']:"",
                    'thumbnail' => isset($loyaltyData['contact_image']) ? $loyaltyData['contact_image'] : '',
                    'contact_id' => isset($loyaltyData['idcrm_contactid'])?$loyaltyData['idcrm_contactid']:"",
                    'date_of_birth' => isset($loyaltyData['birthdate'])?$loyaltyData['birthdate']:"",
                    'owningteam' => isset($loyaltyData['owningteam'])?$loyaltyData['owningteam']:"",
                    'voucher_data' => isset($loyaltyData['voucher_data'])?$loyaltyData['voucher_data']:serialize(array()),
                    'pass_type' => isset($loyaltyData['pass_type'])?$loyaltyData['pass_type']:1,
                ]);

                if (!$result) {
                    \Log::info('Failed to update card information into DB');
                }

            }else{
                return $this->createCard($loyaltyData);
            }

        } else {
            return $this->createCard($loyaltyData);

        }
        return $result;
    }

    private function createCard($loyaltyData){
        $pass = new Passes();
        $pass->card_id = isset($loyaltyData['cardId'])?$loyaltyData['cardId']:"";
        $pass->first_name = isset($loyaltyData['firstname'])?$loyaltyData['firstname']:"";
        $pass->last_name = isset($loyaltyData['lastname'])?$loyaltyData['lastname']:"";
        $pass->created_on = isset($loyaltyData['createdon']) ? $loyaltyData['createdon'] : '';
        $pass->email = isset($loyaltyData['emailaddress1']) ? $loyaltyData['emailaddress1'] : '';
        $pass->phone = isset($loyaltyData['mobilephone']) ? $loyaltyData['mobilephone'] : '';
        $pass->venue_name = isset($loyaltyData['venue_name']) ? $loyaltyData['venue_name'] : '';
        $pass->loyalty_program = isset($loyaltyData['idcrm_programname']) ? $loyaltyData['idcrm_programname'] : '';
        $pass->total_points = isset($loyaltyData['idcrm_totalpoints']) ? $loyaltyData['idcrm_totalpoints'] : '';
        $pass->serial_number = isset($loyaltyData['serial_number'])?$loyaltyData['serial_number']:"";
        $pass->authentication_token = isset($loyaltyData['authenticationToken'])?$loyaltyData['authenticationToken']:"";
        $pass->thumbnail = isset($loyaltyData['contact_image']) ? $loyaltyData['contact_image'] : '';
        $pass->contact_id = isset($loyaltyData['idcrm_contactid'])?$loyaltyData['idcrm_contactid']:"";
        $pass->date_of_birth = isset($loyaltyData['birthdate'])?$loyaltyData['birthdate']:"";
        $pass->owningteam = isset($loyaltyData['owningteam'])?$loyaltyData['owningteam']:"";
        $pass->voucher_data = isset($loyaltyData['voucher_data'])?$loyaltyData['voucher_data']:serialize(array());
        $pass->pass_type = isset($loyaltyData['pass_type'])?$loyaltyData['pass_type']:1;

        $pass->pass_type_id =  isset($loyaltyData['pass_type_identifier'])?$loyaltyData['pass_type_identifier']:"";

        $result = $pass->saveOrFail();
        if (!$result) {
            \Log::info('Failed to store card information into DB');
        }

        return $result;

    }

    public function download_card($serial_number)
    {
        $pass = Passes::where('serial_number', $serial_number)->first();
        $agent = new Agent();

        if (count($pass) > 0) {
            $loyaltyData = array();
            $loyaltyData['cardId'] = $pass->card_id;
            $loyaltyData['firstname'] = $pass->first_name;
            $loyaltyData['lastname'] = $pass->last_name;
            $loyaltyData['createdon'] = $pass->created_on;
            $loyaltyData['emailaddress1'] = $pass->email;
            $loyaltyData['mobilephone'] = $pass->phone;
            $loyaltyData['venue_name'] = $pass->venue_name;
            $loyaltyData['idcrm_programname'] = $pass->loyalty_program;
            $loyaltyData['idcrm_totalpoints'] = $pass->total_points;
            $loyaltyData['serial_number'] = $pass->serial_number;
            $loyaltyData['authenticationToken'] = $pass->authentication_token;
            $loyaltyData['contact_image'] = $pass->thumbnail;
            $loyaltyData['idcrm_contactid'] = $pass->contact_id;
            $loyaltyData['date_of_birth'] = $pass->date_of_birth;
            $loyaltyData['owningteam'] = $pass->owningteam;

            if ($agent->is("iPhone") || $agent->isAndroidOS()) {

                //Immediately render pass
                $this->generate_pass_gcrc($loyaltyData);

            } else {

                return view("passes.gcrc", [
                    'qrcode' => $this->generate($pass->card_id),
                    'pass_data' => $pass
                ]);


            }
        } else {
            return response()->json(array(
                'status' => 401
            ));
        }

    }

    public function trigger_create_voucher()
    {
        $rawInput = fopen('php://input', 'r');
        $tempStream = fopen('php://temp', 'r+');
        stream_copy_to_stream($rawInput, $tempStream);
        rewind($tempStream);
        if (!file_put_contents(getcwd() . '/temp-voucher.txt', $tempStream)) {
            Log::create(['description' => 'Server could not write data to temporary location.']);
            return "Server could not write data to temporary location.";

        }

        $data = file_get_contents(getcwd() . '/temp-voucher.txt');

        $data = $this->formattingData($data);

        if (!isset($data['message'][3]['data'])) {
            Log::create(['description' => 'Error undefined data.Please checking it thank.', 'status' => 1]);
            return "Data still empty";
        }
        $voucher = array();
        foreach ($data['message'][3]['data'] as $key => $value) {
            switch ($value['fieldName']) {
                case IDCRM_ENTITY_RELATE_CONTACT:

                    foreach ($value['data'] as $contact) {

                        switch ($contact['fieldName']) {
                            case FIELD_FIRST_NAME:
                                $voucher[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                break;

                            case FIELD_LAST_NAME:
                                $voucher[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                break;

                            case FIELD_EMAIL:

                                $voucher[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                break;

                            case FIELD_PHONE:
                                $voucher[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                break;
                            case CONTACT_ID:
                            case IDCRM_CONTACT_ID:
                                $voucher["idcrm_contactid"] = isset($contact['value']) ? $contact['value'] : "";
                                break;
                            case IDCRM_BIRTHDAY:

                                $voucher["birthdate"] = isset($contact['value']) ? $contact['value'] : "";
                                break;
                            default:
                                break;
                        }
                    }
                    break;

                case IDCRM_SEND_PASSBOOK:
                    $voucher[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                    break;

                case IDCRM_EXPIRED_DATE:
                    $voucher[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                    break;

                case IDCRM_VOUCHER_NAME:
                    $voucher[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                    break;

                case IDCRM_VOUCHER_AMOUNT:
                    $voucher[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                    break;
                case IDCRM_VOUCHER_ID:
                    $voucher[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                    break;

                case IDCRM_TYPE_OF_VOUCHER:
                    $voucher[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                    break;

                case ENTITIES_IDCRM_LOYALTY_PROGRAM:
                    foreach ($value['data'] as $loyaltyProgram) {
                        if ($loyaltyProgram['fieldName'] == 'idcrm_programname') {
                            $voucher[$loyaltyProgram['fieldName']] = isset($loyaltyProgram['value']) ? $loyaltyProgram['value'] : "";
                        } else if ($loyaltyProgram['fieldName'] == 'owningteam') {
                            $voucher[$loyaltyProgram['fieldName']] = isset($loyaltyProgram['value']) ? $loyaltyProgram['value'] : "";
                        }
                    }
                    break;


                case ENTITIES_CONTACT_ANNOTATION:
                    if (!empty($value['data'])) {
                        $annotation = end($value['data']);

                        if ($annotation['fieldName'] == 'contact_annotation') {
                            $voucher["contact_image"] = isset($annotation['value']) ? $annotation['value'] : "";
                        }
                    }
                    break;

                case "idcrm_relatedloyaltypromotion":
                    foreach ($value['data'] as $relate_loyalty_promotion) {
                        if ($relate_loyalty_promotion['fieldName'] == 'idcrm_promotionname') {
                            $voucher[$relate_loyalty_promotion['fieldName']] = isset($relate_loyalty_promotion['value']) ? $relate_loyalty_promotion['value'] : "";
                        }
                    }
                    break;
                case 'idcrm_relatedloyaltyprogramrule':
                    foreach ($value['data'] as $relate_loyalty_program_rule) {
                        if ($relate_loyalty_program_rule['fieldName'] == 'idcrm_description') {
                            $voucher[$relate_loyalty_program_rule['fieldName']] = isset($relate_loyalty_program_rule['value']) ? $relate_loyalty_program_rule['value'] : "";
                        }else if($relate_loyalty_program_rule['fieldName'] == 'idcrm_promotionearned'){
                            $voucher[$relate_loyalty_program_rule['fieldName']] = isset($relate_loyalty_program_rule['value']) ? $relate_loyalty_program_rule['value'] : "";
                        }else if($relate_loyalty_program_rule['fieldName'] == 'idcrm_pointstoearn'){
                            $voucher[$relate_loyalty_program_rule['fieldName']] = isset($relate_loyalty_program_rule['value']) ? $relate_loyalty_program_rule['value'] : "";
                        }else if($relate_loyalty_program_rule['fieldName'] == 'idcrm_emailtemplate'){
                            $voucher[$relate_loyalty_program_rule['fieldName']] = isset($relate_loyalty_program_rule['value']) ? $relate_loyalty_program_rule['value'] : "";
                        }
                    }
                    break;
                case 'idcrm_voucherstatus':
                    $voucher[$value['fieldName']] = isset($value['label']) ? $value['label'] : "";
                    break;
                case FIELD_IDCRM_CREATE_ON:
                    $voucher[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                    break;
                default:
                    break;
            }

        }


//        $image = $this->covert_base64(isset($voucher["contact_image"]) ? $voucher["contact_image"] : "",
//            isset($voucher['idcrm_contactid']) ? $voucher['idcrm_contactid'] : "",
//            isset($data['message'][1]['PrimaryEntityId']) ? $data['message'][1]['PrimaryEntityId'] : "");
//        if ($image) {
//            $voucher["contact_image"] = $image;
//        } else {
            unset($voucher["contact_image"]);
//        }

        $voucher["cardId"] = isset($data['message'][1]['PrimaryEntityId']) ? $data['message'][1]['PrimaryEntityId'] : "";
        $voucher['authenticationToken'] = PASS_AUTH_TOKEN;
        $voucher['action'] = $data['message'][0]['Action'];
        $voucher['serial_number'] = $voucher["cardId"]; // Serial number is the same as card Id
        $voucher['pass_type'] = 2; // Serial number is the same as card Id
        $voucher['pass_type_identifier'] = PASS_TYPE_IDENTIFIER_VOUCHER; // Serial number is the same as card Id

        $voucher_data = array(
            'idcrm_promotionname' => isset($voucher['idcrm_promotionname'])?$voucher['idcrm_promotionname']:"",
            'idcrm_expirationdate' => isset($voucher['idcrm_expirationdate'])?$voucher['idcrm_expirationdate']:"",
            'idcrm_description' => isset($voucher['idcrm_description'])?$voucher['idcrm_description']:"",
            'idcrm_voucherstatus' => isset($voucher['idcrm_voucherstatus'])?$voucher['idcrm_voucherstatus']:"",
        );

        $voucher['voucher_data'] = serialize($voucher_data);


        if( $voucher['action'] == "Create" &&  (isset($voucher[IDCRM_SEND_PASSBOOK]) && $voucher[IDCRM_SEND_PASSBOOK] == SEND_VOUCHER_OK)){
            $result = $this->_store_card_data($voucher['action'], $voucher);

            if ($result) {
                $serial_voucher =isset($voucher['serial_number'])?$voucher['serial_number']:"";
                $url = "https://umanota.haricrm.com/download_voucher/$serial_voucher";
                $mail_data = array(
                    "url" => $url, // . $voucher['serial_number'],
                    "serial_number" => isset($voucher['serial_number'])?$voucher['serial_number']:"",
                    "contact_name" => "",
                    'first_name' => isset($voucher['firstname']) ? $voucher['firstname'] : "",
                    'last_name' => isset($voucher['lastname']) ? $voucher['lastname'] : "",
                    "loyalty_program" => isset($voucher['idcrm_programname']) ? $voucher['idcrm_programname'] : "",
                    "venue" => isset($voucher['venue_name']) ? $voucher['venue_name'] : "",
                    "template" => isset($voucher['idcrm_emailtemplate'])?$voucher['idcrm_emailtemplate']:'mail_voucher'
                );
                $subject = "Welcome to The Paulistas Clube";
                $title = "Alex at Uma Nota";
                $type = "Alex at Uma Nota";

                $mail_data_id = $this->store_mail_data($mail_data, $type);
                $mail_data['mail_data_id'] = $mail_data_id;

                $this->dispatch(new SendMail($voucher['emailaddress1'], "info@uma-nota.com", $subject, $title, $mail_data));

                $pusback = array(
                    "idcrm_authenticationtoken" => $voucher['authenticationToken'],
                    "idcrm_barcode" => isset($voucher['cardId']) ? $voucher['cardId'] : "",
                    "idcrm_passtypeid" => PASS_TYPE_IDENTIFIER,
                    "idcrm_serialnumber" => isset($voucher['cardId']) ? $voucher['cardId'] : "",
                );

                $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, $voucher['cardId'], IDCRM_ENTITY_VOUCHER_CARD, $pusback));

                return "Card has been Created";
            }

            return "Error Record Local Storage.";

        }else if( $voucher['action'] == "Update" && (isset($voucher[IDCRM_SEND_PASSBOOK]) && $voucher[IDCRM_SEND_PASSBOOK] == SEND_VOUCHER_RESEND)){
            $result = $this->_store_card_data($voucher['action'], $voucher);
            if ($result) {
                $url = "https://umanota.haricrm.com/download_voucher/".$voucher['serial_number'];
                $mail_data = array(
                    "url" => $url,
                    "serial_number" => isset($voucher['serial_number'])?$voucher['serial_number']:"",
                    "contact_name" => "",
                    'first_name' => isset($voucher['firstname']) ? $voucher['firstname'] : "",
                    'last_name' => isset($voucher['lastname']) ? $voucher['lastname'] : "",
                    "loyalty_program" => isset($voucher['idcrm_programname']) ? $voucher['idcrm_programname'] : "",
                    "venue" => isset($voucher['venue_name']) ? $voucher['venue_name'] : "",
                    "template" => 'resend_voucher'
                );
                $subject = "Welcome back to The Paulistas Clube";
                $title = "Alex at Uma Nota";
                $type ='Alex at Uma Nota';
                $mail_data_id = $this->store_mail_data($mail_data, $type);
                $mail_data['mail_data_id'] = $mail_data_id;

                $this->dispatch(new SendMail($voucher['emailaddress1'], "info@uma-nota.com", $subject, $title, $mail_data));

                $pusback = array(
                    "idcrm_sendpassbook" => SEND_VOUCHER_OK
                );

                $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, $voucher['cardId'], "idcrm_loyaltyvoucher", $pusback));
                return "Card has been Resend";
            }

            return "Error Record Local Storage.";

        }

        return ;


    }


    public function download_voucher_card($serial_number)
    {
        $pass = Passes::where(['serial_number' => $serial_number, 'pass_type' => 2])->first();

        $agent = new Agent();
        if (count($pass) > 0) {
            $loyaltyData = array();
            $loyaltyData['cardId'] = $pass->card_id;
            $loyaltyData['firstname'] = $pass->first_name;
            $loyaltyData['lastname'] = $pass->last_name;
            $loyaltyData['createdon'] = $pass->created_on;
            $loyaltyData['emailaddress1'] = $pass->email;
            $loyaltyData['mobilephone'] = $pass->phone;
            $loyaltyData['venue_name'] = $pass->venue_name;
            $loyaltyData['idcrm_programname'] = $pass->loyalty_program;
            $loyaltyData['idcrm_totalpoints'] = $pass->total_points;
            $loyaltyData['serial_number'] = $pass->serial_number;
            $loyaltyData['authenticationToken'] = $pass->authentication_token;
            $loyaltyData['contact_image'] = $pass->thumbnail;
            $loyaltyData['idcrm_contactid'] = $pass->contact_id;
            $loyaltyData['date_of_birth'] = $pass->date_of_birth;
            $loyaltyData['owningteam'] = $pass->owningteam;
            $loyaltyData['voucher_data'] = $pass->voucher_data;
            $loyaltyData['pass_type'] = $pass->pass_type;


//           if ($agent->is("iPhone") || $agent->isAndroidOS()) {

                //Immediately render pass
                $this->_generate_voucher($loyaltyData);

//            } else {
//
//                return view("passes.voucher", [
//                    'qrcode' => $this->generate($pass->card_id),
//                    'pass_data' => $pass
//                ]);
//
//
//            }
        } else {
            return response()->json(array(
                'status' => 401
            ));
        }



    }

    private function _generate_voucher( $loyaltyData )
    {

        $voucher_data = (isset($loyaltyData['voucher_data']) and !empty($loyaltyData['voucher_data']))?unserialize($loyaltyData['voucher_data']):array();
        $image_path = resource_path('images');

        if (!file_exists(WWDR_FILE)) {
            dd("WWDRcertPath not exist");
        }

        if (!file_exists(P12_VOUCHER_FILE)) {
            dd("Certificate not exist");
        }

        $pass = new PKPass();

        $pass->setCertificate(P12_VOUCHER_FILE);
        $pass->setCertificatePassword(P12_PASSWORD_VOUCHER);
        $pass->setWWDRcertPath(WWDR_FILE);

        $standardKeys = [
            'serialNumber' => $loyaltyData['serial_number'],
            'formatVersion' => 1,
            "description" => "The Paulistas Clube",
            'organizationName' => 'The Paulistas Clube',
            'passTypeIdentifier' => PASS_TYPE_IDENTIFIER_VOUCHER,
            'teamIdentifier' => TEAM_IDENTIFIER,
            'webServiceURL' => WEB_SERVICE_URL,
            'authenticationToken' => "vxwxd7J8AlNNFPS8k0a0FfUFtq0ewzFdc"
        ];


        $associatedAppKeys = [];
        $relevanceKeys = [];
        $styleKeys = [
            'coupon' => [
                'headerFields' => [
//                    [
//                        'key' => 'point',
//                        'label' => 'Current Point',
//                        'textAlignment' => 'PKTextAlignmentRight',
//                        'value' => "0",
//                        'changeMessage' => 'Points changed to %@'
//                    ]
                ],
                'primaryFields' => [
                    [
                        "key"=>"offer",
                        "label"=> isset($voucher_data['idcrm_promotionname'])?$voucher_data['idcrm_promotionname']:"",
                        "value"=> "Promo",
                        "changeMessage"=>"Changed to %@"
                    ]
                ],
                'secondaryFields' => [
                    [
                        'key' => 'status',
                        'label' => 'Status',
                        'value' => isset($voucher_data['idcrm_voucherstatus'])?$voucher_data['idcrm_voucherstatus']:"",
                        'changeMessage' => "Changed to %@"
                    ],
                    [

                        'key' => 'expires',
                        'label' => 'Expires',
                        'value' => !empty($voucher_data['idcrm_expirationdate']) ? date("d.m.Y h:i a", strtotime($voucher_data['idcrm_expirationdate'])) : "N/A",
                        'changeMessage' => "Changed to %@"
                    ],

                ],
                'auxiliaryFields' => [
//                    [
//                        'key' => 'phone',
//                        'label' => 'Phone',
//                        'value' =>  "AuxiryFields",
//                        'changeMessage' => "Changed to %@"
//                    ]
                ],
                'backFields' => [
                    [
                        'key' => 'terms',
                        'label' => 'TERMS & CONDITIONS',
                        'value' => isset($voucher_data['idcrm_description'])?$voucher_data['idcrm_description']:"",
                        'changeMessage' => "Changed to %@"
                    ],
                    [
                        'key' => 'passSourceSignature',
                        'label' => 'Powered by HARi crm',
                        'value' => 'For more information or to create your own passes, visit: http://www.haricrm.com
This pass may contain trademarks that are licensed or affiliated with HARi crm.'
                    ]
                ]
            ]
        ];


        $visualAppearanceKeys = [

            'backgroundColor' => 'rgb(255,255,255)',
            'foregroundColor' => 'rgb(188,155,93)',
            'labelColor' => 'rgb(51,51,51)',

            'barcode' => [
                'format' => 'PKBarcodeFormatQR',
                'messageEncoding' => 'iso-8859-1',
                'message' => isset($loyaltyData['serial_number']) ? $loyaltyData['serial_number'] : ""
            ],
        ];

        $webServiceKeys = [];

        $passData = array_merge(
            $standardKeys,
            $associatedAppKeys,
            $relevanceKeys,
            $styleKeys,
            $visualAppearanceKeys,
            $webServiceKeys
        );

        $pass->setJSON(json_encode($passData));

        // Add files to the PKPass package
        $pass->addFile($image_path . '/icon.png');
        $pass->addFile($image_path . '/icon@2x.png');
        $pass->addFile($image_path . '/logo.png');
        $pass->addFile($image_path . '/logo@2x.png');
        $pass->addFile($image_path . '/logo@3x.png');
        $pass->addFile($image_path . '/thumbnail.png');
//        $pass->addFile($image_path . '/strip.png');

        //dd($pass);
        if (!$pass->create(true)) {
            return 'Error: ' . $pass->getError();
        }
    }
    public function trigger_delete_card()
    {
        try {
            $rawInput = fopen('php://input', 'r');
            $tempStream = fopen('php://temp', 'r+');
            stream_copy_to_stream($rawInput, $tempStream);
            rewind($tempStream);
            if (!file_put_contents(getcwd() . '/temp-delete-card.txt', $tempStream)) {
                Log::create(['description' => 'Server could not write data to temporary location.']);
                return "Server could not write data to temporary location.";

            }
            $data = file_get_contents(getcwd() . '/temp-delete-card.txt');
            if (empty($data)) {
                return errorMessage("Data could not be null");
            }
            $data = json_decode($data, true);
            if (!empty($data)) {
                $primaryId = isset($data['PrimaryEntityId']) ? $data['PrimaryEntityId'] : "";
                $deletePass = Passes::where("serial_number", $primaryId)->delete();
                if ($deletePass) {
                    $iosDeviceRegistration = IosDeviceRegistration::where("serial_number", $primaryId)->get();
                    if ($iosDeviceRegistration->count() > 0) {
                        foreach ($iosDeviceRegistration as $key => $value) {
                            $deviceId = $value->device_id;
                            IosDevice::where("device_id", $deviceId)->delete();

                        }

                    }
                    $deleteIosDeviceRegistration = IosDeviceRegistration::where("serial_number", $primaryId)->delete();
                    if ($deleteIosDeviceRegistration) {
                        return "successfully";
                    }

                }
                return "delete Pass Error";
            }
            return "No data get from CRM";

        } catch (\Exception $exception) {
            return "Internal server error please contact to admin" . $exception->getMessage();
        }
    }

    public function trigger_transaction()
    {
        try {
            $rawInput = fopen('php://input', 'r');
            $tempStream = fopen('php://temp', 'r+');
            stream_copy_to_stream($rawInput, $tempStream);
            rewind($tempStream);
            if (!file_put_contents(getcwd() . '/temp-spending.txt', $tempStream)) {
                Log::create(['description' => 'Server could not write data to temporary location.']);
                return "Server could not write data to temporary location.";

            }
            $data = file_get_contents(getcwd() . '/temp-spending.txt');

            $data = $this->formattingData($data);

            if (!isset($data['message'][3]['data'])) {
                Log::create(['description' => 'Error undefined data.Please checking it thank.', 'status' => 1]);
                return "Data still empty";
            }

            //print_r($data['message'][3]['data']);
            $spending_data = array();
            foreach ($data['message'][3]['data'] as $key => $value) {

                switch ($value['fieldName']) {
                    case "idcrm_contact":

                        foreach ($value['data'] as $contact) {

                            switch ($contact['fieldName']) {
                                case FIELD_FIRST_NAME:
                                    $spending_data[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                    break;
                                case FIELD_LAST_NAME:
                                    $spending_data[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                    break;
                                case FIELD_EMAIL:
                                    $spending_data[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                    break;
                                default:
                                    break;
                            }
                        }
                        break;
                    case "idcrm_relatedpromotion":
                        if (!empty($value['data'])) {
                            foreach ($value['data'] as $promotion) {
                                if($promotion['fieldName']=="idcrm_promotionname")
                                {
                                    $spending_data[$promotion['fieldName']] = isset($promotion['value'])?$promotion['value']:"";
                                }
                            }
                        }
                        break;
                    case "idcrm_loyaltycard":
                        if (!empty($value['data'])) {
                            foreach ($value['data'] as $loyalty_card) {
                                switch ($loyalty_card['fieldName']) {
                                    case "idcrm_totalpoints":
                                        $spending_data[$loyalty_card['fieldName']] = isset($loyalty_card['value']) ? $loyalty_card['value'] : "";
                                        break;
                                    case "idcrm_loyaltycardid":
                                        $spending_data[$loyalty_card['fieldName']] = isset($loyalty_card['value']) ? $loyalty_card['value'] : "";
                                        break;
                                    default:
                                        break;
                                }
                            }
                        }

                        break;

                    case ENTITIES_IDCRM_LOYALTY_PROGRAM:
                        foreach ($value['data'] as $loyaltyProgram) {
                            if ($loyaltyProgram['fieldName'] == 'idcrm_programname') {
                                $spending_data[$loyaltyProgram['fieldName']] = isset($loyaltyProgram['value']) ? $loyaltyProgram['value'] : "";
                            } else if ($loyaltyProgram['fieldName'] == 'owningteam') {
                                $loyaltyData[$loyaltyProgram['fieldName']] = isset($loyaltyProgram['value']) ? $loyaltyProgram['value'] : "";
                            }
                        }
                        break;

                    case "idcrm_venue":

                        foreach ($value['data'] as $venue) {
                            if ($venue['fieldName'] == 'idcrm_name') {
                                $spending_data["venue_name"] = isset($venue['value']) ? $venue['value'] : "";
                            }
                        }
                        break;

                    case FIELD_IDCRM_PUSH_STATUS:
                        $spending_data["idcrm_pushstatus"] = isset($value['value']) ? $value['value'] : "";
                        break;
                    case "idcrm_typeoftransaction":
                        $spending_data["idcrm_typeoftransaction"] = isset($value['value']) ? $value['value'] : "";
                        break;
                    case "idcrm_points":
                        $spending_data["points"] = isset($value['value']) ? $value['value'] : "";
                        break;
                    case "idcrm_amount":

                        $spending_data["transaction_amount"] = isset($value['value']) ? $value['value'] : "";
                        break;
                    case "createdon":
                        $spending_data["create_spending_date"] = isset($value['value']) ? $value['value'] : "";
                        break;
                    case "transactioncurrencyid":
                        foreach ($value['data'] as $currency) {
                            if ($currency['fieldName'] == 'isocurrencycode') {
                                $spending_data[$currency['fieldName']] = isset($currency['value']) ? $currency['value'] : "";
                            }
                        }
                        break;
                    default:
                        break;
                }

            }

            // 527210001 =credit ,527210000=debit
            $full_name = isset($spending_data['firstname']) ? $spending_data['firstname'] : "";
            $full_name .= " ";
            $full_name .= isset($spending_data['lastname']) ? $spending_data['lastname'] : "";


            if ($spending_data['idcrm_typeoftransaction'] != SPEDING_TYPE_PROMOTION) {
                return "speading with out send mail";
            }
            $template = "transaction_gcrc";
            $subject = "Great to see you again amigo";

            $title = "Alex at Uma Nota";


            $mail_data = array(
                "url" => "https://umanota.haricrm.com/download_card/" . $spending_data['idcrm_loyaltycardid'],
                "serial_number" => $spending_data['idcrm_loyaltycardid'],
                'first_name' => isset($spending_data['firstname']) ? $spending_data['firstname'] : "",
                'last_name' => isset($spending_data['lastname']) ? $spending_data['lastname'] : "",
                "contact_name" => $full_name,
                "loyalty_program" => isset($spending_data['idcrm_programname']) ? $spending_data['idcrm_programname'] : "",
                "venue" => isset($spending_data['venue_name']) ? $spending_data['venue_name'] : "",
                "current_points" => isset($spending_data['idcrm_totalpoints']) ? $spending_data['idcrm_totalpoints'] : 0,
                "create_spending_date" => date("m/d/Y H:i:s"),
                "transaction_amount" => isset($spending_data['transaction_amount']) ? $spending_data['transaction_amount'] : 0,
                "point" => isset($spending_data['points']) ? $spending_data['points'] : 0,
                "isocurrencycode" => isset($spending_data['isocurrencycode']) ? $spending_data['isocurrencycode'] : "",
                "promotion_name" => isset($spending_data['idcrm_promotionname']) ? $spending_data['idcrm_promotionname'] : "",
                "template" => $template
            );
            $type = 'gcrc';
            $mail_data_id = $this->store_mail_data($mail_data, $type);
            $mail_data['mail_data_id'] = $mail_data_id;

            // Send mail to report customer about transaction
            $this->dispatch(new SendMail($spending_data['emailaddress1'], "contact@viazul.com", $subject, $title, $mail_data));

            return "Success Spending";
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    private function store_mail_data($data, $type)
    {

        $createMailData = MailData::create([
            'card_id' => $data['serial_number'],
            "data" => serialize($data),
            'type' => $data['template'],
            'loyalty_program_type' => $type
        ]);

        return $createMailData->id;

    }

    public function trigger()
    {
        \Log::info('CRM trigger executed ...');
        try {

            // 527210001= ko,527210000=ok, 527210002=resend
            $rawInput = fopen('php://input', 'r');
            $tempStream = fopen('php://temp', 'r+');
            stream_copy_to_stream($rawInput, $tempStream);
            rewind($tempStream);
            if (!file_put_contents(getcwd() . '/temp.txt', $tempStream)) {
                Log::create(['description' => 'Server could not write data to temporary location.']);
                return;
            }
            $data = file_get_contents(getcwd() . '/temp.txt');

            $data = $this->formattingData($data);
            if (!isset($data['message'][3]['data'])) {
                Log::create(['description' => 'Error undefined data.Please checking it thank.', 'status' => 1]);
                return "Data still empty";
            }

            $loyaltyData = array();
            foreach ($data['message'][3]['data'] as $key => $value) {
                switch ($value['fieldName']) {
                    case ENTITIES_IDCRM_CONTACT:

                        foreach ($value['data'] as $contact) {
                            switch ($contact['fieldName']) {
                                case FIELD_FIRST_NAME:
                                    $loyaltyData[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                    break;

                                case FIELD_LAST_NAME:
                                    $loyaltyData[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                    break;


                                case FIELD_EMAIL:
                                    $loyaltyData[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                    break;

                                case FIELD_PHONE:
                                    $loyaltyData[$contact['fieldName']] = isset($contact['value']) ? $contact['value'] : "";
                                    break;
                                case "contactid":
                                case "idcrm_contactid":
                                    $loyaltyData["idcrm_contactid"] = isset($contact['value']) ? $contact['value'] : "";
                                    break;
                                case "birthdate":

                                    $loyaltyData["birthdate"] = isset($contact['value']) ? $contact['value'] : "";
                                    break;
                                default:
                                    break;
                            }
                        }
                        break;

                    case FIELD_IDCRM_TOTAL_POINT:
                        $loyaltyData[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                        break;

                    case ENTITIES_IDCRM_LOYALTY_PROGRAM:
                        foreach ($value['data'] as $loyaltyProgram) {
                            if ($loyaltyProgram['fieldName'] == 'idcrm_programname') {
                                $loyaltyData[$loyaltyProgram['fieldName']] = isset($loyaltyProgram['value']) ? $loyaltyProgram['value'] : "";
                            } else if ($loyaltyProgram['fieldName'] == 'owningteam') {
                                $loyaltyData[$loyaltyProgram['fieldName']] = isset($loyaltyProgram['value']) ? $loyaltyProgram['value'] : "";
                            }
                        }
                        break;

                    case ENTITIES_IDCRM_VENUE_OF_ORIGIN:
                        foreach ($value['data'] as $venue) {
                            if ($venue['fieldName'] == 'idcrm_name') {
                                $loyaltyData["venue_name"] = isset($venue['value']) ? $venue['value'] : "";
                            }
                        }
                        break;

                    case FIELD_IDCRM_PUSH_STATUS:
                        $loyaltyData["idcrm_pushstatus"] = isset($value['value']) ? $value['value'] : "";
                        break;

                    case ENTITIES_CONTACT_ANNOTATION:
                        if (!empty($value['data'])) {
                            $annotation = end($value['data']);

                            if ($annotation['fieldName'] == 'contact_annotation') {
                                $loyaltyData["contact_image"] = isset($annotation['value']) ? $annotation['value'] : "";
                            }
                        }
                        break;
                    case FIELD_IDCRM_CREATE_ON:
                        $loyaltyData[$value['fieldName']] = isset($value['value']) ? $value['value'] : "";
                        break;
                    default:
                        break;
                }

            }


            $image = $this->covert_base64(isset($loyaltyData["contact_image"]) ? $loyaltyData["contact_image"] : "",
                isset($loyaltyData['idcrm_contactid']) ? $loyaltyData['idcrm_contactid'] : "",
                isset($data['message'][1]['PrimaryEntityId']) ? $data['message'][1]['PrimaryEntityId'] : ""
            );
            if ($image) {
                $loyaltyData["contact_image"] = $image;
            } else {
                unset($loyaltyData["contact_image"]);
            }

            $loyaltyData["cardId"] = isset($data['message'][1]['PrimaryEntityId']) ? $data['message'][1]['PrimaryEntityId'] : "";
            $loyaltyData['authenticationToken'] = PASS_AUTH_TOKEN;
            $loyaltyData['action'] = $data['message'][0]['Action'];
            $loyaltyData['serial_number'] = $loyaltyData["cardId"]; // Serial number is the same as card Id
            $loyaltyData['pass_type_identifier'] = PASS_TYPE_IDENTIFIER;

            $full_name = isset($loyaltyData['firstname']) ? $loyaltyData['firstname'] : "";
            $full_name .= " ";
            $full_name .= isset($loyaltyData['lastname']) ? $loyaltyData['lastname'] : "";

            $action = $data['message'][0]['Action'];

            if ((isset($action) && $action == "Update") && (isset($loyaltyData["idcrm_pushstatus"]))) {
                if ($loyaltyData['idcrm_pushstatus'] == PUSH_STATUS_UPDATE_CARD) {
                    \Log::info("Action: Push Status Update Card");
                    $get_contact = Passes::where("contact_id", $loyaltyData['idcrm_contactid'])->first();
                    if ($get_contact) {
                        $result = $this->_store_card_data($action, $loyaltyData);

                        if ($result) {
                            $devices = DB::table('passes')
                                ->join('ios_device_registrations', 'ios_device_registrations.serial_number', '=', 'passes.serial_number')
                                ->join('ios_devices', 'ios_devices.device_id', '=', 'ios_device_registrations.device_id')
                                ->where('passes.contact_id', $loyaltyData['idcrm_contactid'])
                                ->select('ios_devices.*', 'passes.owningteam', "ios_device_registrations.device_type")
                                ->get();

                            foreach ($devices as $device) {
                                \Log::info("Push Notification:" . $device->push_token);
                                \Log::info("Push Owningteam:" . $device->owningteam);
                                \Log::info("Device Type :" . $device->device_type);

                                $this->push_notification($device->push_token, $device->owningteam);

                            }
                            $pusback = array(
                                "idcrm_pushstatus" => PUSH_STATUS_OK,
                                "idcrm_lastuseddate" => time() + date("HKT")
                            );

                            $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, $loyaltyData['cardId'], "idcrm_loyaltycard", $pusback));
                            return "Success Update Contact";
                        }
                        return "Not Found Device to update";
                    }

                } else if ($loyaltyData["idcrm_pushstatus"] == PUSH_STATUS_OK) //Update card
                {
                    Log::create(['description' => 'Successfully Update Loyalty Card.', 'status' => 1]);
                    $pass = Passes::where('card_id', $loyaltyData["cardId"])->first();

                    if ($pass) {
                        if ($loyaltyData['idcrm_totalpoints'] == $pass->total_points) {
                            $this->_store_card_data($action, $loyaltyData);
                            return;
                        }
                    }

                    $result = $this->_store_card_data($action, $loyaltyData);

                    if ($result) {
                        $devices = DB::table('ios_device_registrations')
                            ->join('ios_devices', 'ios_device_registrations.device_id', '=', 'ios_devices.device_id')
                            ->where('ios_device_registrations.serial_number', $loyaltyData['serial_number'])
                            ->select('ios_devices.*', 'passes.owningteam')
                            ->get();

                        foreach ($devices as $device) {
                            \Log::info("Push Notification:" . $device->push_token);
                            \Log::info("Push Owningteam:" . $device->owningteam);
                            $this->push_notification($device->push_token, $device->owningteam);
                        }
                    }
                } else if ($loyaltyData["idcrm_pushstatus"] == PUSH_STATUS_RESEND) //Resend Card
                {
                    Log::create(['description' => 'Successfully Resend Loyalty Card.', 'status' => 1]);

                    $this->_store_card_data($action, $loyaltyData);
                    $mail_data = array(
                        "url" => "https://umanota.haricrm.com/download_card/" . $loyaltyData['serial_number'],
                        "serial_number" => $loyaltyData['serial_number'],
                        "contact_name" => $full_name,
                        'first_name' => isset($loyaltyData['firstname']) ? $loyaltyData['firstname'] : "",
                        'last_name' => isset($loyaltyData['lastname']) ? $loyaltyData['lastname'] : "",
                        "loyalty_program" => isset($loyaltyData['idcrm_programname']) ? $loyaltyData['idcrm_programname'] : "",
                        "venue" => isset($loyaltyData['venue_name']) ? $loyaltyData['venue_name'] : "",
                        "template" => "recovery_gcrc"
                    );
                    $type = "viazul";

                    $mail_data_id = $this->store_mail_data($mail_data, $type);
                    $mail_data['mail_data_id'] = $mail_data_id;
                    // Resend email to customer to download card again
                    $title = "Alex at Uma Nota";
                    $subject = "Your details have been updated";
                    $this->dispatch(new SendMail($loyaltyData['emailaddress1'], "info@uma-nota.com", $subject, $title, $mail_data));
                    $pusback = array(
                        "idcrm_pushstatus" => PUSH_STATUS_OK,
                        "idcrm_lastuseddate" => time() + date("HKT")
                    );

                    $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, $loyaltyData['cardId'], "idcrm_loyaltycard", $pusback));
                }

            } else {

                $result = $this->_store_card_data($action, $loyaltyData);
                if ($result) {
                    $mail_data = array(
                        "url" => "https://umanota.haricrm.com/download_card/" . $loyaltyData['serial_number'],
                        "serial_number" => $loyaltyData['serial_number'],
                        "contact_name" => $full_name,
                        'first_name' => isset($loyaltyData['firstname']) ? $loyaltyData['firstname'] : "",
                        'last_name' => isset($loyaltyData['lastname']) ? $loyaltyData['lastname'] : "",
                        "loyalty_program" => isset($loyaltyData['idcrm_programname']) ? $loyaltyData['idcrm_programname'] : "",
                        "venue" => isset($loyaltyData['venue_name']) ? $loyaltyData['venue_name'] : "",
                        "current_points" => $loyaltyData['idcrm_totalpoints'],
                        "template" => 'mail_gcrc'
                    );
                    $type = "viazul";
                    $mail_data_id = $this->store_mail_data($mail_data, $type);
                    $mail_data['mail_data_id'] = $mail_data_id;
                    $subject = "Welcome to The Paulistas Clube";
                    $title = "Alex at Uma Nota";
                    $this->dispatch(new SendMail($loyaltyData['emailaddress1'], "info@uma-nota.com", $subject, $title, $mail_data));

                    $pass_type_id = PASS_TYPE_IDENTIFIER;

                    $pusback = array(
                        "idcrm_authenticationtoken" => $loyaltyData['authenticationToken'],
                        "idcrm_barcode" => isset($loyaltyData['cardId']) ? $loyaltyData['cardId'] : "",
                        "idcrm_passtypeid" => $pass_type_id,
                        "idcrm_lastuseddate" => time() + date("HKT"),
                        "idcrm_serialnumber" => isset($loyaltyData['cardId']) ? $loyaltyData['cardId'] : "",
                        "idcrm_pushstatus" => PUSH_STATUS_OK
                    );

                    $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, $loyaltyData['cardId'], "idcrm_loyaltycard", $pusback));

                }
            }

            return "Successfully";
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }

    }

    private function _generate()
    {
        $barcode = rand(10, 100) . rand(10, 100) . rand(10, 100) . rand(10, 100) . rand(10, 100) . rand(10, 100);
        $barcode = hash('tiger192,3', $barcode);
        return $barcode;
    }

    public function generate_card($loyaltyData)
    {

        $name = isset($loyaltyData['firstname']) ? $loyaltyData['firstname'] : "";
        $name .= " ";
        $name .= isset($loyaltyData['lastname']) ? $loyaltyData['lastname'] : "";

        $pass = new EventTicket(isset($loyaltyData['cardId']) ? $loyaltyData['cardId'] : "", isset($loyaltyData['idcrm_programname']) ? $loyaltyData['idcrm_programname'] : "");
        $pass->setForegroundColor("rgb(255, 255, 255)");
        $pass->setWebServiceURL(WEB_SERVICE_URL);
        $pass->setAuthenticationToken($loyaltyData['authenticationToken']);
        $pass->setFormatVersion(1);
        $pass->setTeamIdentifier(TEAM_IDENTIFIER);
        $pass->setPassTypeIdentifier(PASS_TYPE_IDENTIFIER);
        $pass->setOrganizationName("Classified Group");

        $pass->setLabelColor("rgb(250, 184, 35)");

        $structure = new Structure();

        $primary = new Field('name', $name);
        $primary->setLabel('Name');
        $structure->addPrimaryField($primary);

        $secondary = new Field('cardId', isset($loyaltyData['cardId']) ? $loyaltyData['cardId'] : "");
        $secondary->setLabel('Card ID');
        $structure->addSecondaryField($secondary);


        $secondary = new Field('since', isset($loyaltyData['createdon']) ? date("d.m.Y", strtotime($loyaltyData['createdon'])) : "");
        $secondary->setLabel('Member Since');
        $secondary->setTextAlignment("PKTextAlignmentLeft");
        $structure->addSecondaryField($secondary);


        $auxiliary = new Field('email', isset($loyaltyData['emailaddress1']) ? $loyaltyData['emailaddress1'] : "");
        $auxiliary->setLabel('Email');
        $structure->addAuxiliaryField($auxiliary);


        $auxiliary = new Field('phone', isset($loyaltyData['mobilephone']) ? $loyaltyData['mobilephone'] : "");
        $auxiliary->setLabel('Phone');
        $structure->addAuxiliaryField($auxiliary);

        $auxiliary = new Field('birthday', '00/00/0000');
        $auxiliary->setLabel('Birthday');
        $auxiliary->setTextAlignment("PKTextAlignmentLeft");

        $structure->addAuxiliaryField($auxiliary);


        $headerField = new Field('point', isset($loyaltyData['idcrm_totalpoints']) ? intval($loyaltyData['idcrm_totalpoints']) : "");
        $headerField->setLabel('Current Point');
        $structure->addHeaderField($headerField);


        $backField1 = new Field("back1", "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.");
        $backField1->setLabel("Team and Conditions");
        $structure->addBackField($backField1);

        $icon = new Image(ICON_FILE, 'icon');
        $pass->addImage($icon);


        $logo = new Image(LOGO_FILE, 'logo');
        $pass->addImage($logo);

        $background = new Image(BACKGROUND_FILE, "background");
        $pass->addImage($background);

        $thumbnail = new Image(PROFILE_FILE, 'thumbnail');
        $pass->addImage($thumbnail);


        $pass->setStructure($structure);

        $barcode = new Barcode(Barcode::TYPE_QR, $loyaltyData['cardId']);
        $barcode->setMessage('message', 'https://www.passsource.com/pass/create.php?hashedSerialNumber=eNortjIysVLKyMow9fdJSo8I8nR2zigrz4601DdNt7VVsgZcMKJ1CbA,');
        $pass->setBarcode($barcode);


        $factory = new PassFactory(PASS_TYPE_IDENTIFIER, TEAM_IDENTIFIER, ORGANIZATION_NAME, P12_FILE, P12_PASSWORD, WWDR_FILE);
        $factory->setOutputPath(OUTPUT_PATH);
        $factory->package($pass);

        $pusback = array(
            "idcrm_authenticationtoken" => $loyaltyData['authenticationToken'],
            "idcrm_barcode" => isset($loyaltyData['cardId']) ? $loyaltyData['cardId'] : "",
            "idcrm_passtypeid" => PASS_TYPE_IDENTIFIER,
            //"idcrm_lastuseddate"=> date("Y-m-d h:m:s"),
            "idcrm_serialnumber" => isset($loyaltyData['cardId']) ? $loyaltyData['cardId'] : "",
            "idcrm_pushstatus" => 527210000
        );

        $this->dispatch(new PushLoyaltyCard(CRM_USER, CRM_PASSWORD, CRM_MODE, CRM_URL, CRM_ORG, $loyaltyData['cardId'], "idcrm_loyaltycard", $pusback));

        if (isset($loyaltyData['action']) && $loyaltyData['action'] == 'update') {
            return view("passbook", ['serialNumber' => ($loyaltyData['cardId']) ? $loyaltyData['cardId'] : ""]);
        }

    }

    public function formattingData($data)
    {

        $output = array();
        $sub_data = array();
        if (empty($data)) {
            return errorMessage("Data could not be null");
        }
        $data = json_decode($data, true);
        if (!empty($data)) {

            foreach ($data as $key => $value) {
                if ($key != "data") {
                    array_push($output, array($key => $value));
                    continue;
                }

                if (isset($data['data'])) {
                    foreach ($data['data'] as $key1 => $value1) {
                        $explodeKey = $this->_explodeKey($value1['Key']);
                        if ($explodeKey == false) {
                            return errorMessage("Error Some Data Format Please checking it.");
                        }
                        $arr = null;
                        if (empty($explodeKey)) {
                            return errorMessage("Error Some Data Format Please checking it.");
                        }
                        switch (array_values($explodeKey)[0]) {
                            case NORMAL:
                                $arr = array(
                                    'fieldName' => array_keys($explodeKey)[0],
                                    'dataType' => array_values($explodeKey)[0],
                                    'value' => isset($value1['Value'][0]['Value']) ? $value1['Value'][0]['Value'] : "",
                                    'label' => '',
                                    'data' => array()
                                );
                                array_push($sub_data, $arr);
                                break;
                            case OPTIONSET:
                                $arr = array(
                                    'fieldName' => array_keys($explodeKey)[0],
                                    'dataType' => array_values($explodeKey)[0],
                                    'value' => isset($value1['Value'][0]['Key']) ? $value1['Value'][0]['Key'] : "",
                                    'label' => isset($value1['Value'][0]['Value']) ? $value1['Value'][0]['Value'] : "",
                                    'data' => array()
                                );
                                array_push($sub_data, $arr);
                                break;

                            case LOOKUP:
                                $lookup_arr = array();

                                if (!empty($value1['Value'])) {
                                    foreach ($value1['Value'] as $key2 => $lookup) {
                                        //dd($lookup);
                                        $explodeKeyLookUp = explode("+", $lookup['Key']);
                                        if (empty($explodeKeyLookUp)) {
                                            return errorMessage("Error Some Data Format Please checking it.");
                                        }
                                        switch ($explodeKeyLookUp[0]) {
                                            case NORMAL:
                                                $sub_lookup_arr = array(
                                                    'fieldName' => isset($explodeKeyLookUp[1]) ? $explodeKeyLookUp[1] : "",
                                                    'dataType' => isset($explodeKeyLookUp[0]) ? $explodeKeyLookUp[0] : "",
                                                    'value' => $lookup['Value'],
                                                    'label' => '',
                                                    'data' => array()
                                                );
                                                array_push($lookup_arr, $sub_lookup_arr);
                                                break;
                                            case LOOKUP:
                                                $sub_lookup_arr = array(
                                                    'fieldName' => isset($explodeKeyLookUp[1]) ? $explodeKeyLookUp[1] : "",
                                                    'dataType' => isset($explodeKeyLookUp[0]) ? $explodeKeyLookUp[0] : "",
                                                    'value' => $lookup['Value'],
                                                    'label' => '',
                                                    'data' => array()
                                                );
                                                array_push($lookup_arr, $sub_lookup_arr);
                                                break;
                                            case OPTIONSET:
                                                $sub_lookup_arr = array(
                                                    'fieldName' => isset($explodeKeyLookUp[1]) ? $explodeKeyLookUp[1] : "",
                                                    'dataType' => isset($explodeKeyLookUp[0]) ? $explodeKeyLookUp[0] : "",
                                                    'value' => isset($explodeKeyLookUp[2]) ? $explodeKeyLookUp[2] : "",
                                                    'label' => $lookup['Value'],
                                                    'data' => array()
                                                );
                                                array_push($lookup_arr, $sub_lookup_arr);
                                                break;

                                            default:
                                                break;
                                        }


                                    }
                                }

                                $arr = array(
                                    'fieldName' => array_keys($explodeKey)[0],
                                    'dataType' => array_values($explodeKey)[0],
                                    'value' => isset($value1['Value'][0]['Value']) ? $value1['Value'][0]['Value'] : "",
                                    'label' => "",
                                    'data' => $lookup_arr

                                );
                                array_push($sub_data, $arr);

                                break;

                            case "annotation":
                                $annotation_arrays = array();
                                if (!empty($value1['Value'])) {

                                    foreach ($value1['Value'] as $key => $annotation) {

                                        $annotation_arr = array(
                                            'fieldName' => array_keys($explodeKey)[0],
                                            'dataType' => array_values($explodeKey)[0],
                                            'value' => $annotation['Value'],
                                            'label' => '',
                                            'data' => array()
                                        );
                                        array_push($annotation_arrays, $annotation_arr);
                                    }
                                }
                                $arr = array(
                                    'fieldName' => array_keys($explodeKey)[0],
                                    'dataType' => array_values($explodeKey)[0],
                                    'value' => "",
                                    'label' => '',
                                    'data' => $annotation_arrays
                                );

                                array_push($sub_data, $arr);
                                break;
                            case "Money":
                                $arr = array(
                                    'fieldName' => array_keys($explodeKey)[0],
                                    'dataType' => array_values($explodeKey)[0],
                                    'value' => isset($value1['Value'][0]['Value']) ? $value1['Value'][0]['Value'] : "",
                                    'label' => '',
                                    'data' => array()
                                );
                                array_push($sub_data, $arr);
                                break;
                            default:

                                break;

                        }

                    }
                    array_push($output, array("data" => $sub_data));
                }
            }


        }

        return successMessage($output);
    }

    private function _explodeKey($key)
    {
        if (!empty($key)) {
            $explode = explode("+", $key);
            return array($explode[0] => $explode[1]);
        }
        return false;
    }

    public function readerCard($serialNumber)
    {
        try {

            if (empty($serialNumber) || $serialNumber == "") {
                return "Please enter serial number of card first";
            }

            return view("passbook", ['serialNumber' => $serialNumber]);
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    public function covert_base64($base64, $contactId, $fileId)
    {

        if ($base64 == "" || $contactId == "") {
            return false;
        }

        $base64 = stripslashes($base64);
        $path = '/contactFile/' . $contactId .'/'. $fileId;
        $path = checkDirectories($path);
        $image = base64_decode($base64);
        $file = fopen($path . "/thumbnail.png", "wb");
        fwrite($file, $image);
        fclose($file);

        return getcwd() . "/contactFile/" . $contactId .'/'. $fileId."/thumbnail.png";

    }

    public function passHtml($card_id)
    {
        $pass = DB::table('passes')->where(['card_id' => $card_id])->first();
        dd($pass);
        if (!$pass) {
            exit('Page 404, error link.');
        }

        return view('passes.view', [
            'pass_data' => $pass,
            'qrcode' => $this->generate($card_id)
        ]);
    }

    public function generate($card_id)
    {
        $size = '150';
        $encoding = 'UTF-8';
        $errorCorrectionLevel = 'L';
        $marginInRows = 4;
        $debug = false;
        $data = urlencode($card_id);
        $size = ($size > 100 && $size < 800) ? $size : 300;
        $encoding = 'ISO-8859-1';
        $errorCorrectionLevel = ($errorCorrectionLevel == 'L' || $errorCorrectionLevel == 'M' || $errorCorrectionLevel == 'Q' || $errorCorrectionLevel == 'H') ? $errorCorrectionLevel : 'L';

        $marginInRows = ($marginInRows > 0 && $marginInRows < 10) ? $marginInRows : 4;

        $debug = ($debug == true) ? true : false;

        $QRLink = "https://chart.googleapis.com/chart?cht=qr&chs=" . $size . "x" . $size . "&chl=" . $data .
            "&choe=" . $encoding .
            "&chld=" . $errorCorrectionLevel . "|" . $marginInRows;

        if ($debug) {
            echo $QRLink;
        }
        return $QRLink;
    }


    public function register_push_android(Request $request)
    {
        \Log::info("Android push registration");
        \Log::info($request);
        return response()->json(array(
            'status' => 200
        ));
    }

}
