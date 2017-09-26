<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Log;
class LogController extends Controller
{

    public function list_log()
    {

        $data = Log::orderBy('id', 'desc')
            ->paginate(50);
        Log::where("status",1)->update(['status' => "0"]);
        $data->setPath("logs");
        return view("log/log",['data' => $data]);
    }
}
