
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
<?php
    $voucher_data = (isset($pass_data['voucher_data']) and !empty($pass_data['voucher_data']))?unserialize($pass_data['voucher_data']):array();
?>
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
                                    {{--<label class="label lebel-size">CURRENT POINT</label>--}}
                                    {{--<div class="span">{{intval($pass_data->total_points)}}</div>--}}
                                </div>
                            </div>
                        </div>

                        <div id="primaryFields" style="background-image: none;background-image: none;padding: 2px!important;">
                              <div class="text-left content">
                                <div class="passField" style="margin-top: 32px;margin-bottom: 15px;">

                                    <p class="text-left" style="font-size: 54px;margin: 0px;margin-bottom: -10px;color:#bc9b5d;" >Promo</p>
                                    <label class="label lebel-size" style="color: #000 !important">{{ isset($voucher_data['idcrm_promotionname'])?$voucher_data['idcrm_promotionname']:"" }}</label>
                                </div>
                            </div>

                            <div class="text-left content">
                                <div class="passField">
                                    <label class="label lebel-size" style="font-weight: 400;">Expires</label><br/>
                                    <label class="span" style="font-weight: 400;font-size: 12px;color:#bc9b5d;">
                                        {{ !empty($voucher_data['idcrm_expirationdate']) ? date("d.m.Y h:i a", strtotime($voucher_data['idcrm_expirationdate'])) : "N/A" }}
                                    </label>
                                </div>
                            </div>
                            <table class="content" style="margin-top: 30px!important;width: 100%;">

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
