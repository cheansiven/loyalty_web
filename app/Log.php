<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table ="logs";

    protected $fillable =["id","description","status"];
}
