@extends('layouts.user')
@section('content')
    <div class="container">
        <div class="content">

                <div class="container">
                    <div class="modal fade" id="myModal" role="dialog" aria-hidden="true" data-backdrop="static"  data-keyboard="false" >
                        <div class="modal-dialog" style="margin-top: 17%">

                            <!-- Modal content-->
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h4 class="modal-title"><?php echo session("card_id")?"Card Already Exists":"Email Already Exists"  ?></h4>
                                </div>
                                <div class="modal-body">

                                            @if(session("card_id"))
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
                                        <form action="yes" method="POST" id="option-no">
                                            {{csrf_field()}}
                                            <input type="submit" class="btn btn-primary" id="btn_resend_card" value="Yes" style="margin-left: 10px;"/>
                                        </form>
                                    </span>
                                    <span class="pull-right">
                                        <form action="no" method="POST" id="option-yes">
                                            {{csrf_field()}}
                                             <input type="submit" class="btn btn-danger" id="btn_back" value="No"/>
                                        </form>
                                   </span>&nbsp;&nbsp;


                                </div>
                            </div>

                        </div>
                    </div>

                <div class="col-md-10 col-md-offset-1 " style="padding-left: 0px;padding-right: 0px;">

                    <div class="panel-body" style="background: white;opacity: 0.9">
                        <p>Be member of our Loyalty program by filling out the form below.</p>
                        <p>Your loyalty will be rewarded for each purchase made in all our venues.</p>
                        <p>You will receive your personal card on your mobile so that you can use it every time you come at one of our venues.</p>
                        <p>Come visit us and enjoy our promotions!</p>
                        <p>Stay tuned!</p>
                        <br>
                        <div class="error_form">
                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                        @foreach ($errors->all() as $error)
                                            <p><i class="fa fa-times" aria-hidden="true"></i><em> {{ $error }}</em></p>
                                        @endforeach
                                </div>
                            @endif
                        </div>


                        @if(Session::has('message'))
                            <div class="alert alert-success"><i class="fa fa-check" aria-hidden="true"></i><em> {{ session('message') }}</em></div>
                        @endif

                        <form id="contact-form" action="contact" method="POST" enctype="multipart/form-data">
                            {{csrf_field()}}

                            <div class="row">
                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <div class="row">
                                        <div class="col-sm-12 col-md-12 col-lg-12">
                                            <div class="form-group">
                                                <label for="firstName">First Name <span class="star">*</span></label>
                                                <input type="text" name="first_name" class="form-control" placeholder="First Name" value="{{ old('first_name') }}" required/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 col-md-12 col-lg-12">
                                            <div class="form-group">
                                                <label for="lastName">Last Name <span class="star">*</span></label>
                                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" required value="{{ old('last_name') }}"/>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12 col-md-12 col-lg-12">
                                            <div class="form-group">
                                                <label for="email">Email <span class="star">*</span></label>
                                                <input type="email" name="email" class="form-control" placeholder="E-mail" required value="{{ old('email') }}"/>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 col-lg-6 text-center">
                                    <div class="form-group">
                                        <label>Thumbnail <span class="star">*</span></label><br/>
                                        <img id='img-upload' style="border: 1px solid #ccc;height: 150px;" src="{{ session("url_path")?session("url_path"):\URL::to('/')."/image/thumbnail.png" }}"/><br/>
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
                                    <div class="form-group">
                                        <label for="mobilePhone">Mobile Phone <span class="star">*</span></label>
                                        <input type="phone" name="mobile_phone" class="form-control" placeholder="Mobile Phone" required value="{{ old('mobile_phone') }}"/>
                                    </div>
                                    </td>
                                </div>
                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="businessPhone">Date of Birth <span class="star">*</span></label>
                                        <div class='input-group date' id='datetimepicker1'>
                                            <input type='text' class="form-control" name="date_of_birth" placeholder="YYYY-MM-DD" readonly required value="{{ old('date_of_birth') }}"/>
                                            <span class="input-group-addon"><i class="fa fa-calendar" aria-hidden="true"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="mobilePhone">Address </label>
                                        <input type="text" name="address" class="form-control" placeholder="Address"  value="{{ old('address') }}"/>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="language">Language</label>
                                        <select name="language" class="form-control" id="language">
                                            <option value="527210000">English</option>
                                            <option value="527210001">Chinese</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6 col-md-6 col-lg-6">
                                    <div class="form-group">
                                        <label for="country">Country</label>
                                        <select name="country" class="form-control">
                                            @if($countries->count() >0)
                                                @foreach($countries as $key=>$value)
                                                    <option value="{{ $value->name }}" <?php echo ($value->code=="HK")? "selected":"" ?>>{{ $value->name  }}</option>
                                                @endforeach

                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group text-left">
                                <input type="submit" name="submit" value="Submit" class="btn btn-primary" id="submit"/>
                            </div>


                        </form>
                    </div>
                </div>
        </div>
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

            $( "#option-no" ).submit(function( event ) {
                $("#myModal").hide();
                $("#loader").show();
            });

            $( "#option-yes" ).submit(function( event ) {
                $("#myModal").hide();
                $("#loader").show();
            });

            $( "#contact-form" ).submit(function( event ) {
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

