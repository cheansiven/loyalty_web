<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $table ="failed_jobs";

    protected $fillable =["id","connection","queue","payload","exception","failed_at"];
}
