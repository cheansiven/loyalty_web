<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//Route::group(array('middleware' => 'forceSSL'), function() {
Route::get("test_template",function(){

    return view("mail.reaching_20000_spend",['serial_number'=>"",'contact_name'=>'test',"url"=>'a',"venue"=>"1",'mail_data_id'=>1]);
});
//    Route::get('/', function () {
//        return view('welcome');
//    });
    Route::get('restart-supervisor', function () {
        echo exec('service supervisor restart');
        return redirect()->back();
    })->middleware('auth');

    Route::get('/restart',function(){
        \Artisan::call('queue:restart');
    });
    Route::get("test_mail",function(){
        return view("mail.recovery_gcrc",['transaction_amount' => "10", 'current_points'=> "1000", 'serial_number'=>"","mail_data_id"=>1,"loyalty_program" => "Umanoto","create_spending_date"=>"","contact_name"=>"Voeun","venue"=>"Test","promotion_name"=>"ss","url"=>"sss"]);
    });
    Route::get('resendMail', 'ContactController@resendMail');

    Route::get('welcome-mail/{serial_number}/{id}', "MailController@getMailTemplate");
    Route::get('welcome-mail/{first_name}/{url}', function () {
        return view("mail.mail");
    });
    Route::get("connection","ContactController@executeConnection");
    Route::get("/", "ContactController@getForm");
    Route::get("/crm", "ContactController@test");
    Route::post('gcrc/submit', 'ContactController@pushContact');
    Route::post("gcrc/no","ContactController@CheckingCard");
    Route::post("gcrc/yes","ContactController@UpdateContactCheckingCard");

    Route::post("user_login","LoyaltyUserController@login");
    Route::post("resend-card","ContactController@resendCard");

    Route::get("success",function(){

        return view("message.success");
    });

    Route::get("contact/ajax",function(){

        return view("contact.ajax");
    });

    Route::post("ajaxImageUpload",function(){
       return "test";
    });

    Route::get('generate-passbook-card', 'PassbookController@generate_card');
    Route::get('formattingData', 'PassbookController@formattingData');
    Route::get('mail', 'MailController@sendMail');
    Route::get('test/{device_token}', 'PassbookController@apns');
    Route::get('push/{device_token}', 'PassbookController@push_notification');
    Route::get('push_test', 'PassbookController@push_test');
    Route::get('readerCard/{serialNumber}', 'PassbookController@readerCard');

    Route::get('test_card/{serial_number}', 'PassbookController@test_card');
    Route::get('create_card', 'PassbookController@create_card');
    Route::get('download_card/{serialNumber}', 'PassbookController@download_card');
    Route::get('download_voucher/{serial_number}', 'PassbookController@download_voucher_card');


    // Standard api route for passbook
    Route::post('/v1/devices/{device_id}/registrations/{pass_type_id}/{serial_number}', 'PassbookController@register_pass');
    Route::get('/v1/devices/{device_id}/registrations/{pass_type_id?}', 'PassbookController@update_pass');
    Route::get('/v1/passes/{pass_type_id}/{serial_number}', 'PassbookController@get_update_pass');
    Route::delete('/v1/devices/{device_id}/registrations/{pass_type_id}/{serial_number}', 'PassbookController@unregister_pass');
    Route::get('/v1/passes/{pass_type_id}/{serial_number}', 'PassbookController@deliver_pass');
    Route::post('/v1/log', 'PassbookController@log');

    // Stand api routes for  walltetpasses.io - (Andriod)
    Route::post('/api/v1/push', 'PassbookController@register_push_android');


    Auth::routes();

    Route::get('home', 'QueueController@index');
    Route::get('logs', "LogController@list_log");
    Route::get('retry/{id}', 'QueueController@retryFailJob');
    Route::get('retry-all', 'QueueController@RetryAllJobFail');
    Route::any('webhook',"PassbookController@trigger");
    Route::any('webhook-transaction',"PassbookController@trigger_transaction");
    Route::any('webhook-delete-card',"PassbookController@trigger_delete_card");
    Route::any('webhook-create-voucher',"PassbookController@trigger_create_voucher");
    Route::get('google-wallet', 'GoogleWalletController@index');
    Route::get('recovery', 'MailController@index');
    Route::post('recovery', 'MailController@recovery');
    Route::get('passview/{card_id}', 'PassbookController@passHtml');

    Route::post('classified/submit', 'ContactController@pushContactClassified');

    Route::get("execute","ContactController@test_connection1");

    Route::get("push_notification_test/{push_token}/{owningteam}/{type}","PassbookController@push_notification");
    Route::get("test_connection","ContactController@test_connection");
    Route::get("test_mail","ContactController@test_mail");
//});
