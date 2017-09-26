<?php

namespace App\Http\Controllers;

use App\Jobs\SendMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\MailData;

class MailController extends Controller
{

    public function __construct()
    {

    }

    public function getMailTemplate($serial_number, $id)
    {
        $data = MailData::where(["id" => $id, "card_id" => $serial_number])->get();

        if ($data->count() > 0) {
            $data = $data[0];
            $mail_data = unserialize($data->data);
            $mail_data['mail_data_id'] = $data->id;
            if(!array_key_exists("promotion_name",$mail_data))  $mail_data['promotion_name']="dessert";
            return view("mail." . $data->type, $mail_data);
        }

        return "Not Found";
    }

}
