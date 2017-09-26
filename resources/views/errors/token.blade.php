@extends('layouts.user')

@section('content')

    <div class="container" style="margin-top: 300px;">
        <div class="row">
            <div class="col-sm-6 col-md-6 col-lg-6 col-sm-offset-3 col-md-offset-3 col-lg-offset-3">
                <h4 style="color: red;font-weight: bold;text-align: center">Token has been expired please <a href="/">click here</a> to retry again.</h4>
            </div>
        </div>
    </div>
@endsection