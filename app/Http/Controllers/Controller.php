<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Jobs\SendMail;
require "Service/DynamicsCrm.php";
require "Service/ClientService.php";

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param $recipient
     * @param $downloadLinks
     * @return mixed
     */
    protected function sendMailNotify($recipient, $downloadLinks)
    {
        $from = "noreply@mail.com";
        $to = $recipient;
        $subject = "Notification";
        $data = array(
            "android_url" => $downloadLinks['ios'],
            "iphone_url" => $downloadLinks['android'],
            "other_template" => $downloadLinks['other'],
            "contact_name" => " ABC",
            "loyalty_program" => "Classified",
            "venue" => "Pizza Company",
            "note_body" => "Simple"
        );

        return $this->dispatch(new SendMail($to, $from, $subject, $data, 'mail.mail'));
    }
}
