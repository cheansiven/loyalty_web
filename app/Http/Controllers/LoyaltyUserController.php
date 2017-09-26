<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use AlexaCRM\CRMToolkit\Settings;
use AlexaCRM\CRMToolkit\Client as Client;
use Illuminate\Support\Facades\Auth;
class LoyaltyUserController extends Controller
{


    public function login(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if($validator->fails()) {

            return $validator->errors()->withInput();
        }

        $connection = $this->credential();
        if($connection == false)
        {
            return \Redirect::back()->withErrors(['msg' =>'Error create credential with CRM']);
        }
        $condition["idcrm_username"] = $request->email;
        $condition["idcrm_password"] = $request->password;

        $userLogin = $this->retrieveCRM($connection, "idcrm_loyaltyuser", $condition);
        if($userLogin)
        {
            $loyaltyUserId = $userLogin[0]->id;

            $venueId = $userLogin[0]->idcrm_venue->id;

            if(isset($userLogin[0]->idcrm_venue->id) and !empty($userLogin[0]->idcrm_venue->id)){
                $venue = $connection->entity("idcrm_venue", $userLogin[0]->idcrm_venue->id);
                $loyaltyProgram = $venue->idcrm_loyaltyprogram;
                $loyaltyProgramId = $loyaltyProgram->id;
                $loyaltyProgramName = $loyaltyProgram->displayName;
                $venue_name = $venue->idcrm_name;
                session([
                    'loyaltyUserId' => $loyaltyUserId,
                    "venueId" =>$venueId,
                    "loyaltyProgramName" =>$loyaltyProgramName,
                    "venueName" =>$venue_name,
                    'login' =>true,
                    "loyaltyProgramId" =>$loyaltyProgramId,


                ]);

               return redirect('contact');

            }


        }else{
            session(['login' =>false]);
            return \Redirect::back()->withErrors(['message' =>'Invalid Username or Password, Please try again.']);
        }

    }





    public function retrieveCRM($connection, $entity_name, $condition = null)
    {
//        $whoAmI = $connection->executeAction( 'WhoAmI' );
        $fetchXML = '<fetch version="1.0" output-format="xml-platform" mapping="logical" distinct="false">';
        $fetchXML .= '<entity name="'.$entity_name.'">';
        $fetchXML .= '<all-attributes /> ';
        $fetchXML .= '<filter type="and">';
//        $fetchXML .= '<condition attribute="ownerid" operator="eq" value="'. str_replace("\n", "",$whoAmI->UserId . PHP_EOL) .'" />';
        if(!empty($condition))
        {
            foreach ($condition as $key=>$value)
            {
                $fetchXML .= '<condition attribute="'. $key .'" operator="eq" value="'. $value .'" />';
            }
        }
        $fetchXML .= '</filter>';
        $fetchXML .= '</entity>';
        $fetchXML .= '</fetch>';
        $entity= $connection->retrieveMultiple($fetchXML);
        if(count($entity->Entities)>0)
        {
            return $entity->Entities;
        }
        return false;
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
}
