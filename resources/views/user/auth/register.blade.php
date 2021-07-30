@extends('user.layout.auth')

@section('content')

<?php $login_user = asset('asset/img/login-user-bg.jpg'); ?>
<div class="full-page-bg" style="background-image: url({{$login_user}});">
<div class="log-overlay"></div>
    <div class="full-page-bg-inner">
        <div class="row no-margin">
            <div class="col-md-6 log-left">
                <span class="login-logo"><img src="{{ Setting::get('site_logo', asset('logo-black.png'))}}"></span>
                <h2>Create your account and get moving in minutes</h2>
                <p>Welcome to {{Setting::get('site_title','Tranxit')}}, the easiest way to get around at the tap of a button.</p>
            </div>
            <div class="col-md-6 log-right">
                <div class="login-box-outer">
                <div class="login-box row no-margin">
                    <div class="col-md-12">
                        <a class="log-blk-btn" href="{{url('login')}}">ALREADY HAVE AN ACCOUNT?</a>
                        <h3>Create a New Account</h3>
                    </div>

                    <div class="print-error-msg">
                        <ul></ul>
                    </div>

                    <div class="col-md-12 exist-msg" style="display: none;">
                        <span class="help-block">
                                <strong>Mobile number already exists!!</strong>
                        </span>
                    </div>
                    


                    <form role="form" method="POST" action="{{ url('/register') }}">

                        <div id="first_step">
                            <div class="col-md-4">
                                <input value="+234" type="text" placeholder="+234" id="country_code" name="country_code" />
                            </div> 
                            
                            <div class="col-md-8">
                                <input type="text" autofocus id="phone_number" class="form-control" placeholder="Enter Phone Number" name="phone_number" value="{{ old('phone_number') }}" data-stripe="number" maxlength="10" onkeypress="return isNumberKey(event);" />
                            </div>


                            <div class="col-md-12 exist-msg" style="display: none;">
                                <span class="help-block">
                                        <strong>Mobile number already exists!!</strong>
                                </span>
                            </div>

                            <div class="col-md-8">
                                @if ($errors->has('phone_number'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('phone_number') }}</strong>
                                    </span>
                                @endif
                            </div>

                             <div class="col-md-12 mobile_otp_verfication" style="display: none;">
                                <input type="text" class="form-control" placeholder="@lang('user.otp')" name="otp" id="otp" value="">

                                @if ($errors->has('otp'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('otp') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <input type="hidden" id="otp_ref"  name="otp_ref" value="" />
                            <input type="hidden" id="otp_phone"  name="phone" value="" />


                            <div class="col-md-12" style="padding-bottom: 10px;" id="mobile_verfication">
                                <input type="button" class="log-teal-btn small" onclick="smsLogin();" value="Verify Phone Number"/>
                            </div>


                            <div class="col-md-12 mobile_otp_verfication" style="padding-bottom: 10px;display:none" id="mobile_otp_verfication">
                                <input type="button" class="log-teal-btn small" onclick="checkotp();" value="Verify Otp"/>
                            </div>

                        </div>

                        {{ csrf_field() }}

                        <div id="second_step" style="display: none;">

                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="First Name" name="first_name" value="{{ old('first_name') }}" data-validation="alphanumeric" data-validation-allowing=" -" data-validation-error-msg="First Name can only contain alphanumeric characters and . - spaces">

                                @if ($errors->has('first_name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('first_name') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" placeholder="Last Name" name="last_name" value="{{ old('last_name') }}" data-validation="alphanumeric" data-validation-allowing=" -" data-validation-error-msg="Last Name can only contain alphanumeric characters and . - spaces">

                                @if ($errors->has('last_name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('last_name') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="col-md-12">
                                <input type="email" class="form-control" name="email" placeholder="Email Address" value="{{ old('email') }}" data-validation="email">

                                @if ($errors->has('email'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('email') }}</strong>
                                    </span>
                                @endif                        
                            </div>

                            <div class="col-md-12">
                                <label class="checkbox-inline"><input type="checkbox" name="gender" value="MALE" data-validation="checkbox_group"  data-validation-qty="1" data-validation-error-msg="Please choose one gender">Male</label>
                                <label class="checkbox-inline"><input type="checkbox" name="gender"  value="FEMALE" data-validation="checkbox_group"  data-validation-qty="1" data-validation-error-msg="Please choose one gender">Female</label>
                                @if ($errors->has('gender'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('gender') }}</strong>
                                    </span>
                                @endif                        
                            </div>

                            <div class="col-md-12">
                                <input type="password" class="form-control" name="password" placeholder="Password" data-validation="length" data-validation-length="min6" data-validation-error-msg="Password should not be less than 6 characters">

                                @if ($errors->has('password'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                @endif
                            </div>

                            <div class="col-md-12">
                                <input type="password" placeholder="Re-type Password" class="form-control" name="password_confirmation" data-validation="confirmation" data-validation-confirm="password" data-validation-error-msg="Confirm Passsword is not matched">

                                @if ($errors->has('password_confirmation'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                                    </span>
                                @endif
                            </div>
                            
                            <div class="col-md-12">
                                <button class="log-teal-btn" type="submit">REGISTER</button>
                            </div>

                        </div>

                    </form>     

                    <div class="col-md-12">
                        <p class="helper">Or <a href="{{route('login')}}">Sign in</a> with your user account.</p>   
                    </div>

                </div>


                <div class="log-copy"><p class="no-margin">{{ Setting::get('site_copyright', '&copy; '.date('Y').' Appoets') }}</p></div>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('scripts')
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
<script type="text/javascript">
    $.validate({
        modules : 'security',
    });
    $('.checkbox-inline').on('change', function() {
        $('.checkbox-inline').not(this).prop('checked', false);  
    });
    function isNumberKey(evt)
    {
        var edValue = document.getElementById("phone_number");
        var s = edValue.value;
        if (event.keyCode == 13) {
            event.preventDefault();
            if(s.length>=10){
                smsLogin();
            }
        }
        var charCode = (evt.which) ? evt.which : event.keyCode;
        if (charCode != 46 && charCode > 31 
        && (charCode < 48 || charCode > 57))
            return false;

        return true;
    }

    function checkotp(){

        var otp = document.getElementById("otp").value;
        var my_otp = $('#otp_ref').val();
        if(otp){
            if(my_otp == otp){
                $(".print-error-msg").find("ul").html('');
                $('#mobile_otp_verfication').html("<p class='helper'> Please Wait... </p>");
                $('#phone_number').attr('readonly',true);
                $('#country_code').attr('readonly',true);
                $('.mobile_otp_verfication').hide();
                $('#second_step').fadeIn(400);
                $('#mobile_verfication').show().html("<p class='helper'> * Phone Number Verified </p>");
                my_otp='';
            }else{
                $(".print-error-msg").find("ul").html('');
                $(".print-error-msg").find("ul").append('<li>Otp not Matched!</li>');
            }
        }
    }



    function smsLogin(){

        $('.exist-msg').hide();
        var countryCode = document.getElementById("country_code").value;
        var phoneNumber = document.getElementById("phone_number").value;
        $('#otp_phone').val(countryCode+''+phoneNumber);
        var csrf = $("input[name='_token']").val();;

            $.ajax({
                url: "{{url('/otp')}}",
                type:'POST',
                data:{ mobile : countryCode+''+phoneNumber,'_token':csrf ,phoneonly:phoneNumber},
                success: function(data) { 

                    if($.isEmptyObject(data.error)){
                        $('#otp_ref').val(data.otp);
                        $('.mobile_otp_verfication').show();
                        $('#mobile_verfication').hide();
                        $('#mobile_verfication').html("<p class='helper'> Please Wait... </p>");
                        $('#phone_number').attr('readonly',true);
                        $('#country_code').attr('readonly',true);
                        $(".print-error-msg").find("ul").html('');
                        $(".print-error-msg").find("ul").append('<li>'+data.message+'</li>');
                    }else{
                        
                        printErrorMsg(data.error);
                    }
                },
                error:function(jqXhr,status) { 
                    if(jqXhr.status === 422) {
                        $(".print-error-msg").show();
                        var errors = jqXhr.responseJSON;

                        $.each( errors , function( key, value ) { 
                            $(".print-error-msg").find("ul").append('<li>'+value+'</li>');
                        }); 
                    } 
                }

                });
    }

    function printErrorMsg (msg) { 

        $(".print-error-msg").find("ul").html('');
        $(".print-error-msg").css('display','block');
       
        $(".print-error-msg").show();
       
        $(".print-error-msg").find("ul").append('<li><p>'+msg+'</p></li>');
        
    }

</script>

@endsection
