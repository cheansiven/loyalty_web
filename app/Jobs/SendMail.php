<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
class SendMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $send_to;

    protected $send_from;

    protected $subject;

    protected $data;

    protected  $title;
    /**
     * Create a new job instance.
     *
     * @return void
     *
     * data =array(
     *  android_url => http://www.loyalty.idcrm.com/getloyalty,
     *  iphone_url => http://www.loyalty.idcrm.com/getloyalty,
     *  other_template => http://www.loyalty.idcrm.com/getloyalty,
     *  contact_name => contact_first_name . " ". contact_last_name,
     *  loyalty_program => loyalty_program,
     *  venue => venue_name,
     *  note_body => note_body
     *
     * )
     */
    public function __construct($send_to, $send_from, $subject, $title, $data)
    {
        $this->send_to = $send_to;
        $this->send_from =$send_from;
        $this->subject =$subject;
        $this->data =$data;
        $this->title =$title;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data =$this->data;
        $template = 'mail.'.$data['template'];

        Mail::send($template, $data, function($message) use ($data){
            $message->from($this->send_from, $this->title);
            $message->to($this->send_to, isset($data['contact_name'])?$data['contact_name']:"");
            $message->subject($this->subject);

        });
    }
}
