<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Log;

use App\Http\Service\ClientService;

class Card extends MainQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    protected $client;

    public function __construct($data)
    {
        $this->data =$data;
        $this->client = new ClientService(CRM_USER , CRM_PASSWORD, CRM_URL);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $connection = $this->_getConnection('connection.txt');

        $request =$this->data;

        if(isset($request["card_id"]) && $request["card_id"] !="")
        {
            $entity = $connection->entity("idcrm_loyaltycard", $request["card_id"]);
            $entity->idcrm_pushstatus = PUSH_STATUS_RESEND;
            $result = $entity->update();

            $voucher_condition['idcrm_relatedloyaltycard'] = $request['card_id'];
            $voucher_condition['idcrm_voucherstatus'] = VOUCHER_STATUS_ACTIVE;
            $check_voucher = $this->client->retriveCrmData("idcrm_loyaltyvoucher", $voucher_condition);
            if(!empty($check_voucher)){
                foreach ($check_voucher as $key=>$check_voucher_result){
                    Log::create(['description'=>"Resend Voucher",'status'=>1]);
                    $entity_voucher = $connection->entity("idcrm_loyaltyvoucher", $check_voucher_result['idcrm_voucherid']);
                    $entity_voucher->idcrm_sendpassbook = SEND_VOUCHER_RESEND;
                    $entity_voucher->update();
                }


            }else {
                Log::create(['description'=>"Resend Card and Create Voucher",'status'=>1]);
                $this->_create_voucher($this->client, $connection, $request["contact_id"], $request["card_id"]);
            }


            if ($result) {
                return Log::create(['description'=>"Card has been resend.",'status'=>1]);
            } else {
                return Log::create(['description'=>"Error Resend card,please check it.",'status'=>1]);
            }

        }else{
            $loyalty_card = $connection->entity("idcrm_loyaltycard");
            $loyalty_card->idcrm_loyaltyprogram = $connection->entity("idcrm_loyaltyprogram", LOYALTY_PROGRAM);
            $loyalty_card->idcrm_contact = $connection->entity("contact", $request["contact_id"]);
            $loyalty_card->idcrm_venueoforigin = $connection->entity("idcrm_venue", VENUE_ORIGIN);
            $loyalty_card->idcrm_loyaltyuser = $connection->entity("idcrm_loyaltyuser",LOYALTY_USER);
            $loyalty_card->idcrm_totalpoints = "0";
            $loyalty_card->idcrm_totalspendings = "0.00";
            $loyalty_card->idcrm_totalvisits = "0";
            $loyalty_card->idcrm_pushstatus = PUSH_STATUS_KO;
            $loyalty_card->idcrm_lastuseddate = time() + date("HKT");
            $loyalty_card->transactioncurrencyid = $connection->entity("transactioncurrency", HK_CURRENCY);
            $loyaltyCardId = $loyalty_card->create();


            $this->_create_voucher( $this->client, $connection, $request["contact_id"], $loyaltyCardId);

            if ($loyaltyCardId) {
                return Log::create(['description'=>"Card has been create.",'status'=>1]);
            }else{
                return Log::create(['description'=>"Error create card,please check it.",'status'=>1]);
            }
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
}
