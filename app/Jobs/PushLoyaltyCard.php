<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use AlexaCRM\CRMToolkit\Client as Client;
use AlexaCRM\CRMToolkit\Settings;
use App\Log;
class PushLoyaltyCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;



    protected $id;

    protected $entities;

    protected $crm_user;

    protected $crm_password;

    protected $crm_connection_mode;

    protected $crm_address;

    protected $org;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $pwd, $connection_mode, $address, $org, $id, $entities, $data )
    {
        $this->data = $data;
        $this->id = $id;
        $this->entities = $entities;

        $this->crm_user = $user;
        $this->crm_password = $pwd;
        $this->crm_connection_mode = $connection_mode;
        $this->crm_address = $address;
        $this->org = $org;
        $this->validateParam();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $connection = $this->credential();

        if($connection ==false)
        {
            Log::create(['description'=>"Error create credential with CRM",'status'=>1]);
            return;
        }

        $entity = $connection->entity($this->entities, $this->id);

        if(!empty($this->data))
        {
            foreach ($this->data as $key=>$value)
            {

                $entity->$key = $value;
            }
            $result = $entity->update();

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


    public function validateParam()
    {

        if(validateUrl($this->crm_address)==false)
        {
            Log::create(['description'=>"CRM password can not null.",'status'=>1]);
            return;
        }

        if(validateEmail($this->crm_user)  == false)
        {
            Log::create(['description'=>"CRM password can not null.",'status'=>1]);
            return;
        }

        if(checkEmptyValue($this->crm_password)==false )
        {
            Log::create(['description'=>"CRM password can not null.",'status'=>1]);
            return;
        }

        if(checkEmptyValue($this->crm_connection_mode)==false){
            Log::create(['description'=>"CRM connection mode can not null.",'status'=>1]);
            return;
        }

        return successMessage('successfully');

    }


    /**
     * credential function for test connection between f2c and Microsoft Dynamic CRM
     */

    public function credential()
    {
        $clientOptions = array(
            'serverUrl' => $this->crm_address,
            'username' => $this->crm_user,
            'password' => $this->crm_password,
            'authMode' => $this->crm_connection_mode
        );

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
