@extends('layouts.user')
<style type="text/css">
    .invalid input:required:invalid {
        border: 2px solid red;
    }

    .invalid input:required:valid {
        background: #fff ;
    }

    input {
        display: block;
        margin-bottom: 10px;
    }
</style>
@section('content')
    <link href="{{ asset('css/wufoo.css') }}" rel="stylesheet"/>
    <link href="{{ asset('css/wufoo-form-gcrc.css') }}" rel="stylesheet"/>
    <script src="{{ asset('js/wufoo.js') }}"></script>
    <div class="container" style="margin-bottom: 50px;padding-left: unset;">
        <div class="modal fade" id="myModal" role="dialog" aria-hidden="true" data-backdrop="static"  data-keyboard="false" >
            <div class="modal-dialog" style="margin-top: 17%">

                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title"><?php echo session("gcrc_card_id")?"Card Already Exists":"Email Already Exists"  ?></h4>
                    </div>
                    <div class="modal-body">

                        @if(session("gcrc_card_id"))
                            Customer with {{ old("email") }} already has a card. <br/>
                            Would you like to update information of the customer? <br/>
                            No: Don’t update, Yes: Update
                        @else
                            {{ old("email") }}  is already in use.<br/>
                            Would you like to update information relating to this email and then create card? <br/>
                            No: Don’t update, Yes: Update
                            @endif

                            </p>
                    </div>
                    <div class="modal-footer">
                                     <span class="pull-right">
                                        <form action="gcrc/yes" method="POST" id="option-no">
                                            {{csrf_field()}}
                                            <input type="submit" class="btn btn-primary" id="btn_resend_card" value="Yes" style="margin-left: 10px;"/>
                                        </form>
                                    </span>
                        <span class="pull-right">
                                        <form action="gcrc/no" method="POST" id="option-yes">
                                            {{csrf_field()}}
                                            <input type="submit" class="btn btn-danger" id="btn_back" value="No"/>
                                        </form>
                                   </span>&nbsp;&nbsp;


                    </div>
                </div>

            </div>
        </div>

        <div class="col-md-10 col-md-offset-1 ">

            <div class="row" style="margin-bottom: 10px;margin-top: 5px;">
                <div style="text-align: center">
                    <a class="" href="/" style="padding:0px 15px!important">
                        @if(isset(\Request::route()->uri)? (\Request::route()->uri != "success"):"" )
                            <img src="{{asset("image/logo@3x.png")}}" style="height: 120px;margin-top: 30px;margin-bottom: 30px;"/>
                        @endif
                    </a>
                </div>
            </div>
            <header id="header" class="info" style="text-align: justify">
                <h4>Welcome to The Paulistas Clube</h4>
            </header>
            <div class="error_form">
                @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        @foreach ($errors->all() as $error)
                            <p><i class="fa fa-times" aria-hidden="true"></i><em> {{ $error }}</em></p>
                        @endforeach
                    </div>
                @endif
            </div>

            <form id="contact-form" action="gcrc/submit" method="POST" enctype="multipart/form-data">
                {{csrf_field()}}

                <div class="row">
                    <div class="col-sm-6 col-md-6 col-lg-6">
                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <ul>

                                    <li id="foli1" class="notranslate">
                                        <label class="desc" id="title1" for="first_name">Name<span id="req_1" class="req">*</span></label>
                                        <span>
                                            <input id="first_name" name="first_name" type="text" class="field text fn" value="{{ old("first_name") }}" size="8" tabindex="1" required/>
                                            <label for="first_name">First</label>
                                        </span>
                                        <span>
                                        <input id="last_name" name="last_name" type="text" class="field text ln" value="{{ old("last_name") }}" size="14" tabindex="2" required />
                                        <label for="last_name">Last</label>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <ul>
                                    <li id="foli3" class="notranslate">
                                        <label class="desc" id="title3" for="email">Email<span id="req_3" class="req">*</span></label>
                                        <div>
                                            <input id="email" name="email" type="email" spellcheck="false" class="field text large" value="{{ old("email") }}" maxlength="255" tabindex="3" required/>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-12 col-md-12 col-lg-12">
                                <ul>
                                    <li id="foli4" class="notranslate">
                                        <label class="desc" id="title4" for="mobile_phone">Mobile Phone<span id="req_4" class="req">*</span></label>
                                        <div>
                                            <input id="mobile_phone" class="field text large" name="mobile_phone" tabindex="4" required type="tel" maxlength="255" value="{{ old("mobile_phone") }}"/>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>

                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-6 text-center">
                        <div class="form-group text-center">
                            <label class="desc">Thumbnail </label><br/>
                            <img id='img-upload' style="border: 1px solid #ccc;height: 150px;" src="{{ session("url_path_gcrc")?session("url_path_gcrc"):\URL::to('/')."/image/thumbnail.png" }}"/><br/>
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <span class="btn btn-primary btn-file" style="width: 140px">Browse… <input type="file" id="photo" name="thumbnail" value="{{ old("thumbnail") }}"/></span>
                                </span>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="row">
                    <div class="col-sm-6 col-md-6 col-lg-6">
                        <ul>
                            <li id="foli4" class="notranslate">
                                <label class="desc" id="title4" for="address">Address</label>
                                <div>
                                    <input id="address" class="field text large" name="address" tabindex="4"  type="text" maxlength="255" value="{{ old("address") }}"/>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-6">
                        <ul>
                            <li id="foli5" class="date eurodate notranslate">
                                <label class="desc" id="title5" for="Field5">Date of Birth <span id="req_4" class="req">*</span></label>
                                <span>
                                        <input id="Field5-1" name="txt_day" type="text" class="field text large" value="{{ old("txt_day") }}" size="5" maxlength="2" tabindex="5" required/>
                                        <label for="Field5-1">DD</label>
                                    </span>
                                <span class="symbol" style="padding-top: 3px;">/</span>
                                <span>
                                        <input id="Field5-2" name="txt_month" type="text" class="field text large" value="{{ old("txt_month") }}" size="5" maxlength="2" tabindex="6" required/>
                                        <label for="Field5-2" >MM</label>
                                    </span>
                                <span class="symbol" style="padding-top: 3px;">/</span>
                                <span>
                                         <input id="Field5" name="txt_year" type="text" class="field text large" value="{{ old("txt_year") }}" size="8" maxlength="4" tabindex="7" required/>
                                        <label for="Field5">YYYY</label>
                                    </span>
                                <span id="cal5" style="padding-top: 3px;">
                                        <img id="pick5" class="datepicker" src="/image/calendar.png"
                                             alt="Pick a date." data-date="2014-26-08" data-date-format="yyyy-dd-mm"/>
                                    </span>

                            </li>
                        </ul>

                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 col-md-6 col-lg-6">
                        <ul>
                            <li id="foli4" class="notranslate">
                                <label class="desc" id="title4" for="country">Country</label>
                                <div>
                                    <select name="country" class="field select large">
                                        @if($countries->count() >0)
                                            @foreach($countries as $key=>$value)
                                                <?php
                                                $select ="";
                                                if(old("country") == $value->name)
                                                {
                                                    $select = "selected";
                                                }else if($value->code=="HK"){
                                                    $select = "selected";
                                                }

                                                ?>
                                                <option value="{{ $value->name }}" {{ $select }}>{{ $value->name  }}</option>
                                            @endforeach

                                        @endif
                                    </select>
                                </div>
                            </li>
                        </ul>

                    </div>
                    <div class="col-sm-6 col-md-6 col-lg-6">
                        <ul>
                            <li id="foli4" class="notranslate">
                                <label class="desc" id="title4" for="language">Language</label>
                                <div>
                                    <select name="language" class="field select large">
                                        <option value="527210000" {{ old("language")=="527210000"?"selected":"" }}>English</option>
                                        <option value="527210001" {{ old("language")=="527210001"?"selected":"" }}>Chinese</option>
                                    </select>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-6 col-md-6 col-lg-6">
                        <ul>
                            <li id="foli4" class="notranslate">
                                <label class="comment" for="comment">Comments</label>
                                <div>
                                    <textarea name="txt_comment" class="field textarea medium" id="comment">{{ old("txt_comment") }}</textarea>

                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                <div style="padding-right: 0px">
                    <ul>
                        <li class="buttons">
                            <div>

                                <input name="saveForm" class="btTxt submit" type="submit" value="Join Us"/>
                            </div>
                        </li>
                    </ul>
                </div>


            </form>
        </div>
        <div id="loader"></div>
    </div>
    @if($errors->any())
        @if($errors->first()==2)
            <script type="text/javascript">
                $(".error_form").hide();
                $(window).on('load',function(){
                    $('#myModal').modal('show');
                });

            </script>

        @endif
    @endif

    <script>
        $.noConflict();
        jQuery(document).ready(function ($) {
            function hasHtml5Validation () {
                return typeof document.createElement('input').checkValidity === 'function';
            }
            if (hasHtml5Validation()) {
                $('#contact-form').submit(function (e) {
                    if (!this.checkValidity()) {
                        $(this).addClass('invalid');
                        e.preventDefault();
                        return false;
                    } else {
                        $("#loader").show();
                        $(this).removeClass('invalid');
                        $('#contact-form').submit();
                        e.preventDefault();
                    }
                });
            };


            $( "#option-no" ).submit(function( event ) {
                $("#myModal").hide();
                $("#loader").show();
            });

            $( "#option-yes" ).submit(function( event ) {
                $("#myModal").hide();
                $("#loader").show();
            });

            $('#datetimepicker1').datepicker({format: 'yyyy-mm-dd'});

            $(document).on('change', '.btn-file :file', function () {
                var input = $(this),
                    label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
                input.trigger('fileselect', [label]);
            });
            $('.btn-file :file').on('fileselect', function (event, label) {


            });
            function readURL(input) {
                if (input.files && input.files[0]) {
                    image =input.files[0];
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#img-upload').attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            $("#photo").change(function () {
                readURL(this);
            });
        });
    </script>
@endsection
