@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-sm-2 col-md-2 col-lg-2">
                <a href="restart-supervisor" class="btn btn-primary"><i class="fa fa-refresh" aria-hidden="true"></i>&nbsp;Restart Supervisor</a>
            </div>
            <div class="col-sm-4 col-md-4 col-lg-4">
                <div class="text-right"><h3>Queue Fail</h3></div>
            </div>
        </div>


        <table class="table table-bordered">
            @if (\Session::has('success'))
                <div class="alert alert-success">
                    <p>{{ \Session::get('success') }}</p>

                </div>
            @endif
            @if(\Session::has('error'))
                <div class="alert alert-danger">
                    <p>{{ \Session::get('error') }}</p>
                </div>
            @endif

            <thead>
            <th>No</th>
            <th>Connection</th>
            <th>Queue</th>
            <th>Payload</th>
            <th>Fail at</th>
            <th>Action</th>
            </thead>
            <tbody>
            @if($data->count() >0)
                <?php $i = 0; ?>

                @foreach($data as $key=>$value)
                    <?php $i++; ?>
                    <tr>
                        <td>{{ $i }}</td>
                        <td>{{ $value->connection }}</td>
                        <td>{{ $value->queue }}</td>
                        <td>

                            @if(isset($value->payload))
                                <?php
                                $payload = json_decode($value->payload, true);
                                if ($payload) {
                                    echo $payload['displayName'];
                                } else {
                                    echo "";
                                }

                                ?>
                            @endif
                        </td>
                        <td>{{$value->failed_at}}</td>
                        <td class="text-center"><a href="retry/{{ $value->id }}" class="btn btn-primary text-center"><i class="fa fa-refresh" aria-hidden="true"></i> Retry</a></td>
                    </tr>

                @endforeach
            @else
                <tr>
                    <td colspan="6" class="text-center">No Data</td>
                </tr>
            @endif
            </tbody>
        </table>
        <div class="row">
            <div class="col-sm-6">
                {{$data->links()}}
            </div>
            <div class="col-sm-6">
                <div class="pull-right">

                    <a href="retry-all" class="btn btn-primary text-center"><i class="fa fa-refresh" aria-hidden="true"></i> Retry All</a>
                </div>
            </div>
        </div>

    </div>
@endsection

