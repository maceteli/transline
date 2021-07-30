@extends('provider.layout.auth')

@section('content')
<div class="col-md-12">
    <a class="log-blk-btn" href="{{ url('/provider/login') }}">@lang('provider.signup.already_register')</a>
    <h3>@lang('provider.signup.sign_up')</h3>
</div>

<div class="col-md-12">
    <form class="form-horizontal" role="form" method="POST" action="{{ url('/provider/register') }}">

        <div id="first_step">
            <div class="col-md-4">
                <input value="+234" type="text" placeholder="+234" id="country_code" name="country_code" />
            </div> 
            
            <div class="col-md-8">
                <input type="phone" autofocus id="phone_number" class="form-control" placeholder="@lang('provider.signup.enter_phone')" name="phone_number" value="{{ old('phone_number') }}" data-stripe="number" maxlength="10" onkeypress="return isNumberKey(event);"/>
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
            <div>
                <input id="fname" type="text" class="form-control" name="first_name" value="{{ old('first_name') }}" placeholder="@lang('provider.profile.first_name')" autofocus data-validation="alphanumeric" data-validation-allowing=" -" data-validation-error-msg="@lang('provider.profile.first_name') can only contain alphanumeric characters and . - spaces">
                @if ($errors->has('first_name'))
                    <span class="help-block">
                        <strong>{{ $errors->first('first_name') }}</strong>
                    </span>
                @endif
            </div>
            <div>
                <input id="lname" type="text" class="form-control" name="last_name" value="{{ old('last_name') }}" placeholder="@lang('provider.profile.last_name')"data-validation="alphanumeric" data-validation-allowing=" -" data-validation-error-msg="@lang('provider.profile.last_name') can only contain alphanumeric characters and . - spaces">            
                @if ($errors->has('last_name'))
                    <span class="help-block">
                        <strong>{{ $errors->first('last_name') }}</strong>
                    </span>
                @endif
            </div>
            <div>
                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="@lang('provider.signup.email_address')" data-validation="email">            
                @if ($errors->has('email'))
                    <span class="help-block">
                        <strong>{{ $errors->first('email') }}</strong>
                    </span>
                @endif
            </div>
            <div>
                <label class="checkbox-inline"><input type="checkbox" name="gender" value="MALE" data-validation="checkbox_group" data-validation-qty="1" data-validation-error-msg="Please choose one gender">@lang('provider.signup.male')</label>
                <label class="checkbox-inline"><input type="checkbox" name="gender" value="FEMALE" data-validation="checkbox_group" data-validation-qty="1" data-validation-error-msg="Please choose one gender">@lang('provider.signup.female')</label>
                @if ($errors->has('gender'))
                    <span class="help-block">
                        <strong>{{ $errors->first('gender') }}</strong>
                    </span>
                @endif
            </div>                        
            <div>
                <input id="password" type="password" class="form-control" name="password" placeholder="@lang('provider.signup.password')" data-validation="length" data-validation-length="min6" data-validation-error-msg="Password should not be less than 6 characters">

                @if ($errors->has('password'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
            </div>    
            <div>
                <input id="password-confirm" type="password" class="form-control" name="password_confirmation" placeholder="@lang('provider.signup.confirm_password')" data-validation="confirmation" data-validation-confirm="password" data-validation-error-msg="Confirm Passsword is not matched">

                @if ($errors->has('password_confirmation'))
                    <span class="help-block">
                        <strong>{{ $errors->first('password_confirmation') }}</strong>
                    </span>
                @endif
            </div>    
            <div>
                <select class="form-control" name="service_type" id="service_type" data-validation="required">
                    <option value="">Select Service</option>
                    @foreach(get_all_service_types() as $type)
                        <option value="{{$type->id}}">{{$type->name}}</option>
                    @endforeach
                </select>

                @if ($errors->has('service_type'))
                    <span class="help-block">
                        <strong>{{ $errors->first('service_type') }}</strong>
                    </span>
                @endif
            </div>
            <div>
                <input id="service-number" type="text" class="form-control" name="service_number" value="{{ old('service_number') }}" placeholder="@lang('provider.profile.car_number')" data-validation="alphanumeric" data-validation-allowing=" -" data-validation-error-msg="@lang('provider.profile.car_number') can only contain alphanumeric characters and - spaces">
                
                @if ($errors->has('service_number'))
                    <span class="help-block">
                        <strong>{{ $errors->first('service_number') }}</strong>
                    </span>
                @endif
            </div>
            <div>
                <input id="service-model" type="text" class="form-control" name="service_model" value="{{ old('service_model') }}" placeholder="@lang('provider.profile.car_model')" data-validation="alphanumeric" data-validation-allowing=" -" data-validation-error-msg="@lang('provider.profile.car_model') can only contain alphanumeric characters and - spaces">
                
                @if ($errors->has('service_model'))
                    <span class="help-block">
                        <strong>{{ $errors->first('service_model') }}</strong>
                    </span>
                @endif
            </div>
            <button type="submit" class="log-teal-btn">
                @lang('provider.signup.register')
            </button>

        </div>
    </form>
</div>
@endsection


@section('scripts')
<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-form-validator/2.3.26/jquery.form-validator.min.js"></script>
<script type="text/javascript">
    var my_otp='';
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



    function smsLogin(){

        $('.exist-msg').hide();
        var countryCode = document.getElementById("country_code").value;
        var phoneNumber = document.getElementById("phone_number").value;
        $('#otp_phone').val(countryCode+''+phoneNumber);
        var csrf = $("input[name='_token']").val();;

            $.ajax({
                url: "{{url('/provider/otp')}}",
                type:'POST',
                data:{ mobile : countryCode+''+phoneNumber,'_token':csrf ,phoneonly:phoneNumber},
                success: function(data) { 

                    if($.isEmptyObject(data.error)){
                     //   my_otp=data.otp;
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


       function checkotp(){

        var my_otp = $('#otp_ref').val();
        var otp = document.getElementById("otp").value;
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



</script>

@endsection