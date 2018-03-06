<?php
/**
 * Created by umanota_loyalty_web.
 * Author: Voeun So
 * Date: 11/7/17
 * Time: 3:29 PM
 */

namespace App\Jobs;

class MainQueue
{
    protected function _create_voucher($client, $connection, $contact_id, $card_id)
    {
        $condition_program_rule['idcrm_ruletype'] = 527210007;
        $programRule = $client->retriveCrmData("new_loyaltyprogramrule", $condition_program_rule);

        if (!empty($programRule)) {
            foreach ($programRule as $key => $loyaltyProgramRule) {
                $voucher = $connection->entity('idcrm_loyaltyvoucher');
                $voucher->idcrm_loyaltyuser = $connection->entity("idcrm_loyaltyuser", LOYALTY_USER);
                $voucher->idcrm_sendpassbook = SEND_VOUCHER_OK;
                $voucher->idcrm_expirationdate = strtotime('+30 days', time()) + date("HKT");
                $voucher->idcrm_typeofvoucher = TYPE_OF_VOUCHER_PROMOTION;
                $voucher->idcrm_relatedcontact = $connection->entity("contact", $contact_id);
                $voucher->idcrm_relatedloyaltyprogram = $connection->entity("idcrm_loyaltyprogram", LOYALTY_PROGRAM);
                if (isset($loyaltyProgramRule['idcrm_promotionearned'])) {
                    $voucher->idcrm_relatedloyaltypromotion = $connection->entity("idcrm_loyaltypromotion", $loyaltyProgramRule['idcrm_promotionearned']);
                }
                $voucher->idcrm_relatedloyaltycard = $connection->entity("idcrm_loyaltycard", $card_id);
                if (isset($loyaltyProgramRule['new_loyaltyprogramruleid'])) {
                    $voucher->idcrm_relatedloyaltyprogramrule = $connection->entity("new_loyaltyprogramrule", $loyaltyProgramRule['new_loyaltyprogramruleid']);
                }
                $voucher->idcrm_voucherstatus = VOUCHER_STATUS_OK;
                $voucher->create();

                if (isset($loyaltyProgramRule['idcrm_pointearned']) and
                    !empty($loyaltyProgramRule['idcrm_pointearned']) and
                    $loyaltyProgramRule['idcrm_pointearned'] > 0
                ) {

                    $loyalty_condition['idcrm_loyaltycardid'] = $card_id;
                    $loyalty_data = $client->retriveCrmData("idcrm_loyaltycard", $loyalty_condition);
                    if ($loyalty_data) {
                        $total_earn_point = isset($loyalty_data['idcrm_totalpoints']) ? (int)$loyalty_data['idcrm_totalpoints'] : 0;
                        $loyalty_card_update = $connection->entity("idcrm_loyaltycard", $card_id);
                        $loyalty_card_update->idcrm_totalpoints = (int)($total_earn_point + $loyaltyProgramRule['idcrm_pointearned']);
                        $loyalty_card_update->update();
                    }
                }
            }
        }

    }

}
