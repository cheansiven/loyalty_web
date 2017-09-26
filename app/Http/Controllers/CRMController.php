<?php

namespace App\Http\Controllers;

use App\Jobs\PushLoyaltyCard;
use Illuminate\Http\Request;
use AlexaCRM\CRMToolkit\Client as Client;
use AlexaCRM\CRMToolkit\Settings;
use AlexaCRM\CRMToolkit\Entity;

class CRMController extends Controller
{


    /**
     * CRMController constructor.
     * @param $user='voeun@idcrm007.onmicrosoft.com'
     * @param $pwd ='1234567aA'
     * @param $connection_mode='OnlineFederation'
     * @param $address='https://idcrm007.crm.dynamics.com'
     * @param $org ="idcrm007"
     */
    public function __construct()
    {

    }


    public function validateParam()
    {

        if(validateUrl($this->crm_address)==false)
        {
            return errorMessage('Invalid CRM Address.');
        }

        if(validateEmail($this->crm_user)  == false)
        {
            return errorMessage('Invalid CRM user.');
        }

        if(checkEmptyValue($this->crm_password)==false )
        {
            return errorMessage('CRM password can not null.');
        }

        if(checkEmptyValue($this->crm_connection_mode)==false){
            return errorMessage('CRM connection mode can not null.');
        }

        return successMessage('successfully');

    }

    public function setPayload($user, $pwd, $connection_mode, $address, $org)
    {

        $this->crm_user = $user;
        $this->crm_password = $pwd;
        $this->crm_connection_mode = $connection_mode;
        $this->crm_address = $address;
        $this->org = $org;
        $this->validateParam();
    }


    public function config()
    {
        $clientOptions = array(
            'serverUrl' => "https://haricrm.crm5.dynamics.com",
            'username' => "crmadmin@idcrm.com",
            'password' => "Nightfa1",
            'authMode' => "OnlineFederation"
        );
        return $clientOptions;
    }

    /**
     * credential function for test connection between f2c and Microsoft Dynamic CRM
     */

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

    /**
     * @param $id=8de195d1-7a1b-e711-8180-e0071b67bbe1
     * @param $entity=idcrm_loyaltycard
     * @param $data=array(
     *  idcrm_authenticationtoken=>'',
     *  idcrm_barcode=>"",
     *  idcrm_devicelibraryid=>"",
     *  idcrm_passtypeid=>"",
     *  idcrm_lastuseddate=>"",
     *  idcrm_pushtoken=>"",
     *  idcrm_serialnumber=>"",
     *  idcrm_pushstatus=> "OK"
     * )
     * @return array
     */
    public function pushLoyaltyCard()
    {
        try
        {
           // dd((new \DateTime())->format('m/d/Y H:i:s'));
            $id= "42b4e544-8029-e711-8155-e0071b67cb31";
            $entities ="idcrm_loyaltycard";
            $data =array(
                "idcrm_authenticationtoken"=> "123445444",
                "idcrm_barcode"=> "a09d1f58-8126-e711-816b-e0071b659ef1",
                "idcrm_passtypeid"=> PASS_TYPE_IDENTIFIER,
                "idcrm_lastuseddate"=> time()+date("Z"),
                "idcrm_serialnumber"=> "1111111111",
                 "idcrm_pushstatus"=> 527210000
            );

            $connection = $this->credential();
            if($connection ==false)
            {
                return errorMessage("Error create credential with CRM");
            }

            if(empty($data))
            {
                return errorMessage("Empty Data Please checking it");
            }

            if(empty($id) || $id == "" || $id ==null)
            {
                return errorMessage("Id still empty please checking it");
            }
            $entity = $connection->entity($entities, $id);

            if(!empty($data))
            {
                foreach ($data as $key=>$value)
                {

                    $entity->$key = $value;
                }
              //  dd($entity);
                $result = $entity->update();
                dd($result);
                if($result)
                {
                    Log::create(['description'=>"Successfully send data back to CRM",'status'=>1]);
                    return;
                }
            }else{
                Log::create(['description'=>"Empty data",'status'=>1]);
                return;
            }
        }
        catch (\Exception $exception)
        {
            return errorMessage($exception->getMessage());
        }

    }



}
