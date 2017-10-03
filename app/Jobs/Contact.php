<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Log;
use App\Http\Service\ClientService;
class Contact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $req;
    public function __construct($request)
    {
        $this->req =$request;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $connection = $this->_getConnection('connection.txt');
        $request =$this->req;
        $contact = $connection->entity('contact');

        $contact->firstname = isset($request["first_name"]) ? $request["first_name"] : "";
        $contact->lastname = isset($request["last_name"]) ? $request["last_name"] : "";
        $contact->emailaddress1 = isset($request["email"]) ? $request["email"] : "";
        $contact->mobilephone = isset($request["mobile_phone"]) ? $request["mobile_phone"] : "";
        $contact->birthdate = isset($request["date_of_birth"]) ? strtotime($request["date_of_birth"]) : "";
        $contact->idcrm_venueoforigin = $connection->entity("idcrm_venue", VENUE_ORIGIN);
        $contact->address1_country = $request["country"];
        $contact->idcrm_language = (int)$request["language"];
        $contact->idcrm_source = "Web Loyalty Registration";
        $contact->address1_line1 = isset($request["address"]) ? $request["address"] : "";
        $contact->description = isset($request["txt_comment"]) ? $request["txt_comment"] : "";
        $contact->transactioncurrencyid = $connection->entity("transactioncurrency", HK_CURRENCY);
        $contactId = $contact->create();

        if ($contactId) {
            if (isset($request['server_path']) and $request['server_path'] !="") {
                $annotation = $connection->entity("annotation");
                $annotation->subject = "idcrm_main_photo";
                $annotation->filename =  $request['original_name'];
                $annotation->documentbody = base64_encode(file_get_contents($request['server_path']));
                $annotation->objectid = $connection->entity("contact", $contactId);
                $annotation->create();
            }

            $loyalty_card = $connection->entity("idcrm_loyaltycard");
            $loyalty_card->idcrm_loyaltyprogram = $connection->entity("idcrm_loyaltyprogram", LOYALTY_PROGRAM);
            $loyalty_card->idcrm_contact = $connection->entity("contact", $contactId);
            $loyalty_card->idcrm_venueoforigin = $connection->entity("idcrm_venue", VENUE_ORIGIN);
            $loyalty_card->idcrm_loyaltyuser = $connection->entity("idcrm_loyaltyuser", LOYALTY_USER);
            $loyalty_card->idcrm_totalpoints = "0";
            $loyalty_card->idcrm_totalspendings = "0.00";
            $loyalty_card->idcrm_totalvisits = "0";
            $loyalty_card->idcrm_pushstatus = PUSH_STATUS_KO;
            $loyalty_card->idcrm_lastuseddate = time() + date("HKT");
            $loyalty_card->transactioncurrencyid = $connection->entity("transactioncurrency", HK_CURRENCY);
            $loyaltyCardId = $loyalty_card->create();
            if ($loyaltyCardId) {
                \Log::info('Card has been created ...');
                $condition['idcrm_ruletype'] = 527210007;
                $programRule = $this->getCrmData("new_loyaltyprogramrule", $condition);
                if(!empty($programRule))
                {
                    \Log::info('Program Rule has welcome Voucher ...');
                    $voucher = $connection->entity('idcrm_loyaltyvoucher');
                    foreach ($programRule as $key => $loyaltyProgramRule){
                        \Log::info('Program Rule has welcome Voucher ...');
                        $voucher->idcrm_loyaltyuser = $connection->entity("idcrm_loyaltyuser", LOYALTY_USER);
                        $voucher->idcrm_sendpassbook = SEND_VOUCHER_OK;
                        $voucher->idcrm_expirationdate = strtotime('+30 days', time()) + date("HKT");
                        $voucher->idcrm_typeofvoucher = TYPE_OF_VOUCHER_PROMOTION;
                        $voucher->idcrm_relatedcontact = $connection->entity("contact", $contactId);
                        $voucher->idcrm_relatedloyaltyprogram = $connection->entity("idcrm_loyaltyprogram", LOYALTY_PROGRAM);
                        if(isset($loyaltyProgramRule['idcrm_promotionearned'])){
                            $voucher->idcrm_relatedloyaltypromotion = $connection->entity("idcrm_loyaltypromotion", $loyaltyProgramRule['idcrm_promotionearned']);
                        }
                        $voucher->idcrm_relatedloyaltycard = $connection->entity("idcrm_loyaltycard", $loyaltyCardId);
                        if(isset($loyaltyProgramRule['new_loyaltyprogramruleid'])){
                            $voucher->idcrm_relatedloyaltyprogramrule = $connection->entity("new_loyaltyprogramruleid", $loyaltyProgramRule['new_loyaltyprogramruleid']);
                        }
                        $voucher->idcrm_voucherstatus = VOUCHER_STATUS_OK;
                        $voucher->create();
                        if(isset($loyaltyProgramRule['new_loyaltyprogramruleid']) and
                            !empty($loyaltyProgramRule['new_loyaltyprogramruleid']) and
                            $loyaltyProgramRule['new_loyaltyprogramruleid']>0){

                            $loyalty_condition['idcrm_loyaltycardid'] = $loyaltyCardId;
                            $loyalty_data = $this->getCrmData('idcrm_loyaltycard', $loyalty_condition);
                            if(!empty($loyalty_data))
                            {
                                $total_earn_point = isset($loyalty_data[0]['idcrm_totalpoints'])?(int) $loyalty_data[0]['idcrm_totalpoints']:0;
                                $loyalty_card_update = $connection->entity("idcrm_loyaltycard", $loyaltyCardId);
                                $loyalty_card_update->idcrm_totalpoints = (int) ( $total_earn_point + $loyaltyProgramRule['idcrm_pointearned']);
                                $loyalty_card_update->update();
                            }



                        }
                    }
                }else{
                    \Log::info('Program Rule no have welcome Voucher ...');
                }




                if(isset($request['server_path']) && file_exists($request['server_path']))
                {
                    unlink($request['server_path']);
                }

                Log::create(['description'=>"Successfully Create Contact and card for new customer.",'status'=>1]);
                return;
            }else{
                Log::create(['description'=>"Error create loyalty card.",'status'=>1]);
                return;
            }
        } else {
            Log::create(['description'=>"Error create contact.",'status'=>1]);
            return;
        }
    }

    private function _getConnection($connection_name)
    {

        $connection_path = getcwd();
        $connection_path = str_replace("public", "", $connection_path);
        $connection_path = $connection_path = (env("APP_ENV") == 'local')?$connection_path . "/resources/connection/".$connection_name: "/var/www/umanota_loyalty_web/resources/connection/".$connection_name;
        $connection = file_get_contents($connection_path);
        $connection = unserialize($connection);
        return $connection;

    }


    private function getCrmData($entity, $condition)
    {

        $client = new ClientService("hariservice.umanota@haricrm.com","Nightfa1","https://haricrm.crm5.dynamics.com");
        $output =  $client->retriveCrmData($entity, $condition);


        return $output;
    }



}
