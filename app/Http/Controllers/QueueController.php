<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Queue;
use Illuminate\Console\Command;

class QueueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $queue_fail = Queue::paginate(50);
        $queue_fail->setPath("home");
        return view('home', ['data' => $queue_fail]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function retryFailJob($id)
    {
        $retry = \Artisan::call("queue:retry", ['id' => [$id]]);

        if ($retry == 0) {
            \Session::flash('error', 'Error Retry Queue Please try again.');

            return \Redirect::back();
        }

        \Session::flash('message', "Successfully retry Queue.");
        return \Redirect::back();

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function RetryAllJobFail()
    {
        $id =[];
        $queue_fail = Queue::all();
        if($queue_fail->count() > 0)
        {
            foreach ($queue_fail as $key=>$value)
            {
                array_push($id, $value->id);
            }
        }
        $retry = \Artisan::call("queue:retry",['id'=>$id]);

        if ($retry == 0) {
            \Session::flash('error', 'Error Retry Queue Please try again.');

            return \Redirect::back();
        }

        \Session::flash('message', "Successfully retry Queue.");
        return \Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
