<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class GoogleWalletController extends Controller
{
    /**
     * GoogleWalletController constructor.
     */
    public function __construct()
    {

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $client = new \Google_Client();
        // Set application name.
        $client->setApplicationName(APPLICATION_NAME);
        // Set Api scopes.
        $client->setScopes(array(SCOPES));
        // Set your cached access token. Remember to replace $_SESSION with a
        // real database or memcached.
        session_start();
        if (isset($_SESSION['service_token'])) {
            $client->setAccessToken($_SESSION['service_token']);
        }
        // Load the key in PKCS 12 format (you need to download this from the
        // Google API Console when the service account was created.
        $key = file_get_contents(SERVICE_ACCOUNT_PRIVATE_KEY);



        $cred = new \Google_Auth_AssertionCredentials(
            SERVICE_ACCOUNT_EMAIL_ADDRESS,
            array('https://www.googleapis.com/auth/wallet_object.issuer'),
            $key
        );


        $client->setAssertionCredentials($cred);
        if ($client->getAuth()->isAccessTokenExpired()) {
            $client->getAuth()->refreshTokenWithAssertion($cred);
        }
        $_SESSION['service_token'] = $client->getAccessToken();


        // Wallet object service instance.
        $service = new \Google_Service_Walletobjects($client);

        $renderSpecs = array(
            array('templateFamily' => '1.loyalty_list',
                'viewName' => 'g_list'),
            array('templateFamily' => '1.loyalty_expanded',
                'viewName' => 'g_expanded'));
        // Define text module data.
        $textModulesData = array(
            array(
                'header' => 'Rewards details',
                'body' => 'Welcome to Baconrista rewards.  Enjoy your rewards for being a loyal customer. ' .
                    '10 points for every dollar spent.  Redeem your points for free coffee, bacon and more!'
            )
        );

        // Define links module data.
        $linksModuleData = new \Google_Service_Walletobjects_LinksModuleData();
        $uris = array(
            array(
                'uri' => 'http://maps.google.com/map?q=google',
                'kind' => 'walletobjecs#uri',
                'description' => 'Nearby Locations'
            ),
            array(
                'uri' => 'tel:6505555555',
                'kind' => 'walletobjecs#uri',
                'description' => 'Call Customer Service'
            )
        );
        $linksModuleData->setUris($uris);


        $uriModuleImageInstance = new \Google_Service_Walletobjects_Uri();
        $uriModuleImageInstance->setUri(
            'http://farm4.staticflickr.com/3738/12440799783_3dc3c20606_b.jpg'
        );
        $uriModuleImageInstance->setDescription('Coffee beans');
        $imageModuleImageInstance = new \Google_Service_Walletobjects_Image();
        $imageModuleImageInstance->setSourceUri($uriModuleImageInstance);
        $imagesModuleData = new \Google_Service_Walletobjects_ImageModuleData();
        $imagesModuleData->setMainImage($imageModuleImageInstance);
        $imagesModuleDataArr = array($imagesModuleData);

        // Messages to be displayed to all users of Wallet Objects.
        $messages = array(array(
            'header' => 'Welcome to Banconrista Rewards!',
            'body' => 'Featuring our new bacon donuts.',
            'kind' => 'walletobjects#walletObjectMessage'
        ));
        $locations = array(
            array(
                'kind' => 'walletobjects#latLongPoint',
                'latitude' => 37.424015499999996,
                'longitude' => -122.09259560000001
            ),
            array(
                'kind' => 'walletobjects#latLongPoint',
                'latitude' => 37.424354,
                'longitude' => -122.09508869999999
            ),
            array(
                'kind' => 'walletobjects#latLongPoint',
                'latitude' => 37.7901435,
                'longitude' => -122.39026709999997
            ),
            array(
                'kind' => 'walletobjects#latLongPoint',
                'latitude' => 40.7406578,
                'longitude' => -74.00208940000002
            )
        );
        // Source uri of program logo.
        $uriInstance = new \Google_Service_Walletobjects_Uri();
        $imageInstance = new \Google_Service_Walletobjects_Image();
        $uriInstance->setUri(
            'http://farm8.staticflickr.com/7340/11177041185_a61a7f2139_o.jpg'
        );
        $imageInstance->setSourceUri($uriInstance);
        // Create wallet class.
        $wobClass = new \Google_Service_Walletobjects_LoyaltyClass();
        $wobClass->setId('2945482443380251551.ExampleClass1');
        $wobClass->setIssuerName('Baconrista');
        $wobClass->setProgramName('Baconrista Rewards');
        $wobClass->setProgramLogo($imageInstance);
        $wobClass->setRewardsTierLabel('Tier');
        $wobClass->setRewardsTier('Gold');
        $wobClass->setAccountNameLabel('Member Name');
        $wobClass->setAccountIdLabel('Member Id');
        $wobClass->setRenderSpecs($renderSpecs);
        $wobClass->setLinksModuleData($linksModuleData);
        $wobClass->setTextModulesData($textModulesData);
        $wobClass->setImageModulesData($imagesModuleDataArr);
        $wobClass->setMessages($messages);
        $wobClass->setReviewStatus('underReview');
        $wobClass->setAllowMultipleUsersPerObject(true);
        $wobClass->setLocations($locations);
//         dd($wobClass);
        $service->loyaltyclass->insert($wobClass);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
