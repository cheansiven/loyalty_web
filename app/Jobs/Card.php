<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Log;
class Card implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;
    public function __construct($data)
    {
        $this->data =$data;
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
