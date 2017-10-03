<?php

define("DEVICE_IPHONE","iphone");
define("DEVICE_ANDROID","android");
define("DEVICE_WINDOWS_PHONE","windows phone");
define("STATUS","status");
define("MESSAGE","message");
define("SUCCESS","1");
define("ERROR","0");
define("LOOKUP","lookup");
define("NORMAL","normal");
define("OPTIONSET","optionSet");
define("MONEY","Money");


define("BASE_CERTIFICATE_PATH", str_replace("public","",getcwd())."resources/certificate/");

define("WWDR_FILE",BASE_CERTIFICATE_PATH.'AppleWWDRCA.pem');
define("PASS_AUTH_TOKEN",'vxwxd7J8AlNNFPS8k0a0FfUFtq0ewzFdc');

//Card
define("P12_FILE",BASE_CERTIFICATE_PATH.'pass.com.idcrmltd.umanota.p12');
define("PUSH_NOTIFICATION_CERT",BASE_CERTIFICATE_PATH.'pushumanota1.pem');
define("P12_PASSWORD",'1111');
define("PASS_TYPE_IDENTIFIER", "pass.com.idcrmltd.umanota");

//voucher
define("P12_VOUCHER_FILE",BASE_CERTIFICATE_PATH.'pass.com.idcrmltd.umanotavoucher.p12');
define("P12_PASSWORD_VOUCHER",'1111');
define("PASS_TYPE_IDENTIFIER_VOUCHER", "pass.come.idcrmltd.umanotavoucher");

define("TEAM_IDENTIFIER", "7Y4PN8538L");

define("ORGANIZATION_NAME", "Soteca");
define("WEB_SERVICE_URL", "https://umanota.haricrm.com");
define("OUTPUT_PATH",getcwd().'/Output.raw');
define("ICON_FILE", getcwd().'/image/image.png');
define("LOGO_FILE", getcwd().'/image/logo.png');
define("PROFILE_FILE", getcwd().'/image/Profile.png');
define("BACKGROUND_FILE", getcwd().'/image/BG.png');


define('SERVICE_ACCOUNT_EMAIL_ADDRESS', '967263854788-compute@developer.gserviceaccount.com');
define('ISSUER_ID', '2945482443380251551');
define('SERVICE_ACCOUNT_PRIVATE_KEY', BASE_CERTIFICATE_PATH.'wallet-72f450a5b47c.p12');
define('APPLICATION_NAME', 'Wallet Objects Demo');
// Application origins for save to wallet button.
$ORIGINS = array('http://localhost:8080');
// Type of request.
define('SAVE_TO_ANDROID_PAY', 'savetoandroidpay');
define('LOYALTY_WEB', 'loyaltywebservice');
// Api scopes url.
define('SCOPES', 'https://www.googleapis.com/auth/wallet_object.issuer');
//Target audience for JWT.
define('AUDIENCE', 'google');
// Wallet objects API classes and objects ids.
define('LOYALTY_CLASS_ID', 'LoyaltyClass');
define('LOYALTY_OBJECT_ID', 'LoyaltyObject');
define('OFFER_CLASS_ID', 'OfferClass');
define('OFFER_OBJECT_ID', 'OfferObject');
define('GIFTCARD_CLASS_ID', 'GiftCardClass');
define('GIFTCARD_OBJECT_ID', 'GiftCardObject');



///CRM Constant
define('ENTITIES_IDCRM_LOYALTY_USER', 'idcrm_loyaltyuser');
define('FIELD_FIRST_NAME', 'firstname');
define('FIELD_LAST_NAME', 'lastname');
define('FIELD_IDCRM_CREATE_ON', 'createdon');
define('FIELD_EMAIL', 'emailaddress1');
define('FIELD_PHONE', 'mobilephone');
define('FIELD_IDCRM_TOTAL_POINT', 'idcrm_totalpoints');
define('ENTITIES_IDCRM_LOYALTY_PROGRAM', 'idcrm_loyaltyprogram');
define('ENTITIES_IDCRM_VENUE_OF_ORIGIN', 'idcrm_venueoforigin');
define('ENTITIES_IDCRM_CONTACT', 'idcrm_contact');
define('ENTITIES_CONTACT_ANNOTATION', 'contact_annotation');
define('FIELD_IDCRM_PUSH_STATUS', 'idcrm_pushstatus');

// CRM Loyalty Card status
define('PUSH_STATUS_OK', 527210000);
define('PUSH_STATUS_KO', 527210001);
define('PUSH_STATUS_RESEND', 527210002);
define('PUSH_STATUS_UPDATE_CARD', 527210003);
define('SPEDING_TYPE_DEBIT', 527210000);
define('SPEDING_TYPE_CREDIT', 527210001);
define('SPEDING_TYPE_PROMOTION', 527210002);


define('CRM_USER', 'hariservice.umanota@haricrm.com');
define('CRM_PASSWORD', 'Nightfa1');
define('CRM_MODE', 'OnlineFederation');
define('CRM_URL', 'https://haricrm.crm5.dynamics.com');
define('CRM_ORG', 'orga082d66f');

define("VENUE_ORIGIN","0698356c-da57-e711-8147-e0071b67cb41");
define("LOYALTY_USER","dcb6cefd-92a2-e711-8193-e0071b67bbe1");
define("LOYALTY_PROGRAM","36698e90-93a2-e711-8193-e0071b67bbe1");
define("HK_CURRENCY","43e15dfa-1ec0-e611-8100-3863bb3eb0d0");


define("LOYALTY_PROGRAM_TEAM_LYN","60e156db-48ee-e611-810c-3863bb350fc0");



//voucher


define('SEND_VOUCHER_OK', 527210001);
define('SEND_VOUCHER_NO', 527210000);
define('SEND_VOUCHER_RESEND', 527210002);


define('TYPE_OF_VOUCHER_PROMOTION', 527210001);
define('VOUCHER_STATUS_OK', 527210000);


define('IDCRM_SEND_PASSBOOK', "idcrm_sendpassbook");
define('IDCRM_EXPIRED_DATE', "idcrm_expirationdate");
define('IDCRM_VOUCHER_NAME', "idcrm_name");
define('IDCRM_VOUCHER_AMOUNT', "idcrm_voucheramount");
define('IDCRM_VOUCHER_ID', "idcrm_voucherid");
define('IDCRM_TYPE_OF_VOUCHER', "idcrm_typeofvoucher");
define('CONTACT_ID', "contactid");
define('IDCRM_CONTACT_ID', "idcrm_contactid");
define('IDCRM_BIRTHDAY', "birthdate");
define('IDCRM_ENTITY_RELATE_CONTACT', "idcrm_relatedcontact");
define('IDCRM_ENTITY_VOUCHER_CARD', "idcrm_loyaltyvoucher");