<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- CSRF Token -->
	<meta name="csrf-token" content="{{ csrf_token() }}">

	<title>{{ config('app.name', 'Laravel') }}</title>

	<!-- Styles -->
	<link href="{{ asset('css/app.css') }}" rel="stylesheet"/>
	<link href="{{ asset('css/font/font-awesome.min.css') }}" rel="stylesheet"/>
	<link href="{{ asset('css/gcrc_card.css') }}" rel="stylesheet"/>

	<!-- Scripts -->
	<script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
	</script>

</head>
<body>

	<div class="container text-center">
        <div class="flex-center position-ref full-height vertical-center" style="margin-top: 50px">
            <div class="content">

                <div class="row">
                	<div id="passCard">
	                	<div id="passFront" class="pass">
							<div class="decoration"></div>
							<div id="headerFields">
								<h1><img src="{{ \Illuminate\Support\Facades\URL::to("/").'/image/gcrc/logo@3x.png' }}" style="margin-top: 10px;margin-bottom: 10px"/></h1>
								<div>
										<div class="passField" style="text-align: inherit;">
												<label class="label lebel-size">CURRENT POINT</label>
												<div class="span" style="color: #bc9b5d;">{{intval($pass_data->total_points)}}</div>
										</div>
								</div>
							</div>
							<br>
							<div id="primaryFields" style="background-image: none;">
								<table class="content">
									<tbody>
										<tr class="passField">
											<td valign="top" colspan="2">
												<label class="label lebel-size">NAME</label>
												<label class="name">{{($pass_data->first_name . ' ' . $pass_data->last_name)}}</label>
											</td>
											<td rowspan="2" valign="top" align="right">
												<?php
													$path= "";
													if(!empty($pass_data->thumbnail)){
                                                        $arr = explode("public",$pass_data->thumbnail);
                                                        $path = $arr[1];
													}



												?>
												<img src="{{ empty($pass_data->thumbnail)? \Illuminate\Support\Facades\URL::to("/")."/image/Profile.png" : \Illuminate\Support\Facades\URL::to("/"). $path }}" height="120" />
											</td>
										</tr>
										<tr class="passField">
											<td colspan="2">
												<label class="label lebel-size">EMAIL</label>
												<label class="span email">{{($pass_data->email)}}</label>
											</td>
										</tr>
										<tr class="passField">
											<td valign="top" style="width: 33%;">
												<label class="label lebel-size">PHONE</label>
												<label class="span">
													{{($pass_data->phone == null ? 'N/A' : $pass_data->phone)}}
												</label>
											</td>
											<td valign="top" style="width: 33%;">
												<label class="label lebel-size">DATE OF BIRTH</label>
												<label class="span">
													{{($pass_data->date_of_birth == null ? 'N/A' : date("d.m.Y",strtotime($pass_data->date_of_birth)))}}
												</label>
											</td>
											<td valign="top" style="width: 34%;">
												<label class="label lebel-size" style="text-align: right;">MEMBER SINCE</label>
												<label class="span text-right" style="text-align: right;">
													{{ date("d.m.Y",strtotime($pass_data->created_on)) }}
												</label>
											</td>
										</tr>
									</tbody>
									<tfoot>
										<tr>
											<td id="barcode" colspan="3" align="center">

												<div style="display: inline-block; left: 50%;">
													<img src="{{$qrcode}}" alt="QRCODE">
												</div>
											</td>
										</tr>
									</tfoot>
								</table>
							</div>
							<span class="infoButton" onclick="showPassBack(event);">i</span>
						</div>

                	</div>
                	<br>
					<p class="_foot">Power By <span><img style="margin-top: -4px;" src="{{ asset('image/PowerbyWeb.png') }}"></span><br/>For more information, visit: <a href="http://www.haricrm.com" target="_blank">http://www.haricrm.com</a> </p>
                </div>


            </div>
        </div>
    </div>
	<br>

</body>

</html>
