<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IosDeviceRegistration extends Model
{
    protected $fillable = ['device_id','pass_type_id','serial_number'];
    protected $table = 'ios_device_registrations';
}
