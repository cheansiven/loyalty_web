<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IosDevice extends Model
{
    protected $fillable = ['device_id','push_token'];
    protected $table = 'ios_devices';
}
