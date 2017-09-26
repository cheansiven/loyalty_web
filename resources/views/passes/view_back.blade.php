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
	<link href="{{ asset('css/card.css') }}" rel="stylesheet"/>

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
								<h1><img src="{{ \Illuminate\Support\Facades\URL::to("/").'/image/logo.png' }}" style="margin-top: 27px;"/></h1>
								<div>
									<div class="passField" style="text-align: inherit;">
										<label class="label lebel-size" style="text-align: right;  margin-top: -2px !important;">CURRENT POINT</label>
										<label class="span text-right" style="text-align: right; font-size: 20px !important;  margin-top: -5px !important;">
											{{intval($pass_data->total_points)}}
										</label>

									</div> 
								</div>
							</div>
							<br>
							<div id="primaryFields" style="background-image: none;">
								<table class="content">
									<tbody>
										<tr class="passField">
											<td valign="top">
												<label class="label lebel-size">NAME</label>
												<label class="name">{{($pass_data->first_name . ' ' . $pass_data->last_name)}}</label>
											</td>
											<td rowspan="2" valign="top" align="right">
												<img src="{{ empty($pass_data->thumbnail)? \Illuminate\Support\Facades\URL::to("/")."/image/Profile.png" : \Illuminate\Support\Facades\URL::to("/")
												.'/contactFile/'.$pass_data->contact_id."/thumbnail.png" }}" height="120" />
											</td>
										</tr>
										<tr class="passField">
											<td>
												<label class="label lebel-size">EMAIL</label>
												<label class="span email">{{($pass_data->email)}}</label>
											</td>

										</tr>
										<tr class="passField">
											<td>
												<label class="label lebel-size">PHONE</label>
												<label class="span">{{($pass_data->phone)}}</label>	
											</td>
											<td>
												<label class="label lebel-size">DATE OF BIRTH</label>
												<label class="span">{{!empty($loyaltyData['date_of_birth'])?(date(strtotime("d.m.Y", $pass_data->date_of_birth)) ):"N/A"}}</label>
											</td>
											<td>
												<label class="label lebel-size">MEMBER SINCE</label>
												<div class="span">{{ date("d.m.Y",strtotime($pass_data->created_on)) }}</div>
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