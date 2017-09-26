<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Passes extends Model
{
    protected $fillable = [
        'first_name','last_name','created_on','email','phone','venue_of_origin',
        'venue_of_origin','loyalty_program','total_points','serial_number',
        'authentication_token', 'pass_type_id','thumbnail','contact_id','date_of_birth','owningteam',
        'voucher_data', 'pass_type'
    ];

    protected $table = 'passes';
}
