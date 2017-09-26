<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MailData extends Model
{
    protected $table ="mail_data";

    protected $fillable =["card_id", "data", "type","loyalty_program_type"];
}
