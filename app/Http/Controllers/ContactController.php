<?php

namespace App\Http\Controllers;

use App\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\CRMController;
use AlexaCRM\CRMToolkit\Client as OrganizationService;
use AlexaCRM\CRMToolkit\Settings;
use AlexaCRM\CRMToolkit\Client as Client;
use Illuminate\Support\Facades\Input;
use League\Flysystem\Exception;
use PhpParser\Node\Expr\Cast\Double;
use App\Countries;
use App\Jobs\Contact;
use App\Jobs\ContactAndCard;
use App\Jobs\Card;
use App\Http\Service\ClientService;
use Illuminate\Support\Facades\Mail;
class ContactController extends Controller
{

    public function __construct()
    {

    }

    public function resendMail()
    {
        header('Access-Control-Allow-Origin: *');
        \Log::info("Resend Mail has been Execute");
    }

    public function index()
    {

        return view('contact.index', ['status' => 'start']);
    }

    public function success()
    {
        return view("message.success");
    }

    public function test_mail()
    {

        $connection = $this->_getConnection("connection.txt");

        $card = $connection->entity('idcrm_loyaltycard', '46b1f359-c9ac-e711-8155-e0071b67cb41');
        $card->idcrm_viptreament = VALUE_IDCRM_VIP_TREAMENT_YES;
        $card->idcrm_lastuseddate = time() + date("HKT");

        dd($card->update());




    }

    public function getForm()
    {
        $countries = Countries::orderBy('name')->get();
        return view("contact.gcrc", ['countries' => $countries]);
    }


    public function test()
    {
        $client = new ClientService("hariservice.umanota@haricrm.com","Nightfa1","https://haricrm.crm5.dynamics.com");
        $connection_path = getcwd();
        $connection_path = str_replace("public", "", $connection_path);
        $connection_path = $connection_path . "resources/connection/gcrc_client.txt";
        file_put_contents($connection_path, serialize($client));
    }


    protected function validator(array $data, array $rule)
    {
        return Validator::make($data, $rule);
    }


    public function CheckingCard()
    {
        if (file_exists(session("server_path"))) {
            unlink(session("server_path"));
        }

        $data = array(
            "contact_id" => session()->has("gcrc_contact_id") ? session("gcrc_contact_id") : "",
            "card_id" => session()->has("gcrc_card_id") ? session("gcrc_card_id") : "",
        );

        $this->ClearSession();
        $this->dispatch(new Card($data));
        return redirect("success");


    }


    public function UpdateContactCheckingCard()
    {

        $contact_data = $this->_get_local_storage();
        $this->ClearSession();
        $this->dispatch(new ContactAndCard($contact_data));

        return redirect("success");
    }



    private function _get_local_storage()
    {

        $contact_data = array(
            "first_name" => session()->has("first_name_gcrc") ? session("first_name_gcrc") : "",
            "last_name" => session()->has("last_name_gcrc") ? session("last_name_gcrc") : "",
            "email" => session()->has("email_gcrc") ? session("email_gcrc") : "",
            "address" => session()->has("address") ? session("address") : "",
            "txt_comment" => session()->has("comment") ? session("comment") : "",
            "mobile_phone" => session()->has("mobile_phone_gcrc") ? session("mobile_phone_gcrc") : "",
            "date_of_birth" => session()->has("date_of_birth_gcrc") ? session("date_of_birth_gcrc") : "",
            "country" => session()->has("country_gcrc") ? session("country_gcrc") : "",
            "language" => session()->has("language_gcrc") ? session("language_gcrc") : "",
            "server_path" => session()->has("server_path_gcrc") ? session("server_path_gcrc") : "",
            "original_name" => session()->has("original_name_gcrc") ? session("original_name_gcrc") : "",
            "contact_id" => session()->has("gcrc_contact_id") ? session("gcrc_contact_id") : "",
            "card_id" => session()->has("gcrc_card_id") ? session("gcrc_card_id") : "",
            "city" => session()->has("city") ? session("city") : "",

        );

        return $contact_data;
    }


    public function checkRecordCrmExists($connection, $entity_name, $condition = null)
    {
        $fetchXML = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">';
        $fetchXML .= '<entity name="' . $entity_name . '">';
        $fetchXML .= '<all-attributes /> ';
        $fetchXML .= '<filter type="and">';
        if (!empty($condition)) {
            foreach ($condition as $key => $value) {
                $fetchXML .= '<condition attribute="' . $key . '" operator="eq" value="' . $value . '" />';
            }
        }
        $fetchXML .= '</filter>';
        $fetchXML .= '</entity>';
        $fetchXML .= '</fetch>';
        $entity = $connection->retrieveMultiple($fetchXML);
        if (count($entity->Entities) > 0) {
            return $entity->Entities[0];
        }
        return false;
    }


    private function _covertStringToDate($data)
    {
        return $data['txt_year'] . '-' . $data['txt_month'] . '-' . $data['txt_day'];
    }


    private function ClearSession()
    {

        if (session()->has('url_path_gcrc')) session()->forget("url_path_gcrc");
        if (session()->has('server_path_gcrc')) session()->forget("server_path_gcrc");
        if (session()->has('original_name_gcrc')) session()->forget("original_name_gcrc");
        if (session()->has('first_name_gcrc')) session()->forget("first_name_gcrc");
        if (session()->has('last_name_gcrc')) session()->forget("last_name_gcrc");
        if (session()->has('email_gcrc')) session()->forget("email_gcrc");
        if (session()->has('mobile_phone_gcrc')) session()->forget("mobile_phone_gcrc");
        if (session()->has('date_of_birth_gcrc')) session()->forget("date_of_birth_gcrc");
        if (session()->has('country_gcrc')) session()->forget("country_gcrc");
        if (session()->has('language_gcrc')) session()->forget("language_gcrc");
        if (session()->has('gcrc_card_id')) session()->forget("gcrc_card_id");
        if (session()->has('gcrc_contact_id')) session()->forget("gcrc_contact_id");
        if (session()->has('address')) session()->forget("address");
        if (session()->has('txt_comment')) session()->forget("txt_comment");
        if (session()->has('city')) session()->forget("city");

    }


    public function pushContact(Request $request)
    {
        try {
            $validator = Validator::make(Input::all(), [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'mobile_phone' => 'required',
                'txt_day' => 'required',
                'txt_year' => 'required',
                'txt_month' => 'required'
            ]);
            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator->errors())->withInput(Input::all());

            }

            if (array_key_exists("thumbnail",Input::all())) {
                $path = "/temp-image";
                $checkdir = checkDirectories($path);

                if (session()->has("server_path_gcrc") && (session("original_name_gcrc") != $_FILES["thumbnail"]['name'])) {
                    if (file_exists(session("server_path_gcrc"))) {
                        unlink(session("server_path_gcrc"));
                    }
                }


                $path_file_name = $checkdir . '/' . rand(1, 100) . rand(1, 100) . rand(1, 100) . rand(1, 100) . rand(1, 100) . '.png';

                move_uploaded_file($_FILES["thumbnail"]["tmp_name"], $path_file_name);

                session([
                    "url_path_gcrc" => \URL::to('/') . "/" . $path_file_name,
                    'server_path_gcrc' => getcwd() . '/' . $path_file_name,
                    "original_name_gcrc" => $_FILES["thumbnail"]['name']
                ]);


            }
            $this->_store_local_data($request);

            $condition['emailaddress1'] = $request->email;
            $client = new ClientService("hariservice.umanota@haricrm.com","Nightfa1","https://haricrm.crm5.dynamics.com");
            $checkContactExists =  $client->retriveCrmData("contact", $condition);

            if (count($checkContactExists) >0) {
                $checkContactExists =$checkContactExists[0];

                session(["gcrc_contact_id" => isset($checkContactExists['contactid'])?$checkContactExists['contactid']:""]);
                $loyalty_card_condition['idcrm_contact'] = isset($checkContactExists['contactid'])?$checkContactExists['contactid']:"";
                $loyalty_card_condition['idcrm_loyaltyprogram'] = LOYALTY_PROGRAM;
                $checkLoyaltyCard = $client->retriveCrmData('idcrm_loyaltycard', $loyalty_card_condition);

                if (count($checkLoyaltyCard) > 0) {
                    $checkLoyaltyCard = $checkLoyaltyCard[0];
                    session(["gcrc_card_id" => isset($checkLoyaltyCard['idcrm_cardid'])?$checkLoyaltyCard['idcrm_cardid']:""]);
                }
                return \Redirect::back()
                    ->withInput(Input::all())
                    ->withErrors(['status' => 2, 'msg' => 'Contact already exists']);
            }

            $contact_data =$this->_get_local_data($request);
            $this->ClearSession();

            $this->dispatch(new Contact($contact_data));

            return redirect("success");
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }


    }

    private function _store_local_data($request)
    {

        session([
            "first_name_gcrc" => $request->first_name,
            "last_name_gcrc" => $request->last_name,
            "email_gcrc" => $request->email,
            "mobile_phone_gcrc" => $request->mobile_phone,
            "date_of_birth_gcrc" => $this->_covertStringToDate($request->all()),
            "country_gcrc" => $request->country,
            "language_gcrc" => $request->language,
            "comment" =>$request->txt_comment,
            "address" =>$request->address,
            "city" =>$request->city,
        ]);


    }

    private function _get_local_data($request)
    {

        $contact_data = array(
            "server_path" => session()->has("server_path_gcrc") ? session("server_path_gcrc") : "",
            "original_name" => session()->has("original_name_gcrc") ? session("original_name_gcrc") : ""
        );

        $contact_data["first_name"]=$request->first_name;
        $contact_data["last_name"]=$request->last_name;
        $contact_data["email"]=$request->email;
        $contact_data["mobile_phone"]=$request->mobile_phone;
        $contact_data["date_of_birth"]= $this->_covertStringToDate($request->all());
        $contact_data["country"]=$request->country;
        $contact_data["language"]=$request->language;
        $contact_data["address"]= $request->address;
        $contact_data["txt_comment"]= $request->txt_comment;
        $contact_data["city"]= $request->city;

        return $contact_data;
    }


    public function config()
    {
        $clientOptions = array(
            'serverUrl' => "https://haricrm.crm5.dynamics.com",
            'username' => "hariservice.umanota@haricrm.com",
            'password' => "Nightfa1",
            'authMode' => "OnlineFederation"
        );
        return $clientOptions;
    }

    public function executeConnection()
    {

        $connection = $this->credential();
        $connection_path = getcwd();
        $connection_path = str_replace("public", "", $connection_path);
        $connection_path = $connection_path . "resources/connection/connection.txt";
        file_put_contents($connection_path, serialize($connection));
    }

    /**
     * credential function for test connection between f2c and Microsoft Dynamic CRM
     */

    public function test_connection1()
    {
        dd(Log::all());
        $connection = $this->credential();
        $entity = $connection->entity('idcrm_loyaltyvoucher', $this->id);



       dd($entity);
    }
    public function credential()
    {

        $clientOptions = $this->config();

        try {
            $clientSettings = new Settings($clientOptions);

            $client = new Client($clientSettings);
            if ($clientSettings->hasOrganizationData()) {
                return $client;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function test_conneciton($connection_name)
    {
        $connection_path = getcwd();
        $connection_path = str_replace("public", "", $connection_path);
        dd((env("APP_ENV") == 'local')?$connection_path . "/resources/connection/".$connection_name: "/var/www/umanota_loyalty_web/resources/connection/".$connection_name);
    }

    private function _getConnection($connection_name)
    {

        $connection_path = getcwd();
        $connection_path = str_replace("public", "", $connection_path);
        $connection_path = (env("APP_ENV") == 'local') ? $connection_path . "resources/connection/".$connection_name: "/var/www/umanota_loyalty_web/resources/connection/".$connection_name;
        $connection = file_get_contents($connection_path);
        $connection = unserialize($connection);
        return $connection;

    }

}
