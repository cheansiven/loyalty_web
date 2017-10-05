<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Log;
class ContactAndCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $data;

    public function __construct( $data)
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


        $all_data =$this->data;

        $contact = $connection->entity('contact', $all_data['contact_id']);

        $contact->firstname = isset($all_data['first_name']) ? $all_data['first_name'] : "";
        $contact->lastname = isset($all_data['last_name']) ? $all_data['last_name'] : "";
        $contact->emailaddress1 = isset($all_data['email']) ? $all_data['email'] : "";
        $contact->mobilephone = isset($all_data['mobile_phone']) ? $all_data['mobile_phone'] : "";
        $contact->birthdate = isset($all_data['date_of_birth'])? strtotime($all_data['date_of_birth']) + date("HKT") : time()+date("HKT");
        $contact->idcrm_venueoforigin = $connection->entity("idcrm_venue", VENUE_ORIGIN);
        $contact->address1_country = isset($all_data['country'])?$all_data['country'] : "";
        $contact->idcrm_language = isset($all_data['language']) ? (int)$all_data['language'] : 527210000;
        $contact->idcrm_source = "Web Loyalty Registration";
        $contact->address1_line1 = isset($all_data["address"]) ? $all_data["address"] : "";
        $contact->description = isset($all_data["txt_comment"]) ? $all_data["txt_comment"] : "";
        $contact->transactioncurrencyid = $connection->entity("transactioncurrency", HK_CURRENCY);
        $contact->address1_city = isset($request["city"]) ? $request["city"] : "";
        $contactId = $contact->update();

        if($contactId)
        {

            if(isset($all_data["server_path"]) and $all_data["server_path"] !="")
            {
                $annotation_condition['objectid'] = $all_data['contact_id'];
                $annotation_condition['subject'] = "idcrm_main_photo";
                $annotation_data = $this->checkRecordCrmExists($connection, "annotation",$annotation_condition);
                if ($annotation_data) {
                    $annotation_data->subject = "idcrm_main_photo";
                    $annotation_data->documentbody = base64_encode(file_get_contents($all_data["server_path"]));
                    $annotation_data->update();
                }else{
                    $annotation = $connection->entity("annotation");
                    $annotation->subject = "idcrm_main_photo";
                    $annotation->filename = $all_data["original_name"];
                    $annotation->documentbody = base64_encode(file_get_contents($all_data['server_path']));
                    $annotation->objectid = $connection->entity("contact", $all_data['contact_id']);
                    $annotation->create();
                }
            }


            if(isset($all_data["server_path"]) && file_exists($all_data["server_path"]))
            {
                unlink($all_data["server_path"]);
            }

            if(isset($all_data['card_id']) and $all_data['card_id'] !="")
            {
                $entity = $connection->entity("idcrm_loyaltycard", $all_data['card_id']);
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
                $loyalty_card->idcrm_contact = $connection->entity("contact", $all_data['contact_id']);
                $loyalty_card->idcrm_venueoforigin = $connection->entity("idcrm_venue", VENUE_ORIGIN);
                $loyalty_card->idcrm_loyaltyuser = $connection->entity("idcrm_loyaltyuser", LOYALTY_USER);
                $loyalty_card->idcrm_totalpoints = "0";
                $loyalty_card->idcrm_totalspendings =  "0.00";
                $loyalty_card->idcrm_totalvisits = "0";
                $loyalty_card->idcrm_pushstatus = PUSH_STATUS_KO;
                $loyalty_card->idcrm_lastuseddate = time() + date("HKT");
                $loyalty_card->transactioncurrencyid = $connection->entity("transactioncurrency", HK_CURRENCY);

                $loyaltyCardId = $loyalty_card->create();
                if ($loyaltyCardId) {
                    return Log::create(['description'=>"Successfully create new card for old customer",'status'=>1]);
                }else{
                    return Log::create(['description'=>"Error create new card for old customer",'status'=>1]);
                }
            }


        }
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
