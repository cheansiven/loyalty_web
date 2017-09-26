<?php

return array(

    'appNameIOS'     => array(
        'environment' =>'development',
        'certificate' => resource_path('certificate').'/CertPushNotification.pem',
        'passPhrase'  =>'1111',
        'service'     =>'apns'
    ),
    'appNameAndroid' => array(
        'environment' =>'production',
        'apiKey'      =>'yourAPIKey',
        'service'     =>'gcm'
    )

);