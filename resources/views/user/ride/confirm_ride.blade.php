@extends('user.layout.base')

@section('title', 'Ride Confirmation ')

@section('styles')
<style type="text/css">
    .surge-block{
        background-color: black;
        width: 50px;
        height: 50px;
        border-radius: 25px;
        margin: 0 auto;
        padding: 10px;
        padding-top: 15px;
    }
    .surge-text{
        top: 11px;
        font-weight: bold;
        color: white;
    }
</style>
@endsection

@section('content')
<div class="col-md-9">
    <div class="dash-content">
        <div class="row no-margin">
            <div class="col-md-12">
                <h4 class="page-title">@lang('user.ride.ride_now')</h4>
            </div>
        </div>
        @include('common.notify')
        <div class="row no-margin">
            <div class="col-md-6">
                <form action="{{url('create/ride')}}" method="POST" id="create_ride">

                {{ csrf_field() }}
                    <dl class="dl-horizontal left-right">
                        <dt>@lang('user.type')</dt>
                        <dd>{{$service->name}}</dd>
                        <dt>@lang('user.total_distance')</dt>
                        <dd>{{distance($fare->distance)}}</dd>
                        <dt>@lang('user.eta')</dt>
                        <dd>{{$fare->time}}</dd>
                        <dt>@lang('user.estimated_fare')</dt>
                        <dd>{{currency($fare->estimated_fare)}}</dd>
                        <dt>@lang('user.promocode')</dt>
                        <dd id="promo_amount">{{currency()}}</dd>
                        <hr>
                        <dt>@lang('user.total')</dt>
                        <dd id="total_amount">{{currency($fare->estimated_fare - 0)}}</dd>
                        <hr>
                        @if(Auth::user()->wallet_balance > 0)

                        <input type="checkbox" name="use_wallet" value="1"><span style="padding-left: 15px;">@lang('user.use_wallet_balance')</span>
                        <br>
                        <br>
                            <dt>@lang('user.available_wallet_balance')</dt>
                            <dd>{{currency(Auth::user()->wallet_balance)}}</dd>
                        @endif
                    </dl>

                    <input type="hidden" name="s_address" value="{{Request::get('s_address')}}">
                    <input type="hidden" name="d_address" value="{{Request::get('d_address')}}">
                    <input type="hidden" name="s_latitude" value="{{Request::get('s_latitude')}}">
                    <input type="hidden" name="s_longitude" value="{{Request::get('s_longitude')}}">
                    <input type="hidden" name="d_latitude" value="{{Request::get('d_latitude')}}">
                    <input type="hidden" name="d_longitude" value="{{Request::get('d_longitude')}}">
                    <input type="hidden" name="service_type" value="{{Request::get('service_type')}}">
                    <input type="hidden" name="distance" value="{{$fare->distance}}">
                    @if(Request::get('rental_hours') != '') 
                    <input type="hidden" name="rental_hours" value="{{Request::get('rental_hours')}}">
                    @endif
                    <p>@lang('user.promocode')</p>
                    <select class="form-control" name="promocode_id" id="promocode">
                     <option value="" data-percent="0" data-max="0">@lang('user.promocode_select')</option>
                    @foreach($promolist as $promo)
                      <option value="{{$promo->id}}" data-percent = "{{$promo->percentage}}" data-max= "{{$promo->max_amount}}">{{$promo->promo_code}}</option>
                    @endforeach
                    </select>
                    <br>
                    <p>@lang('user.payment_method')</p>
                    <select class="form-control" name="payment_mode" id="payment_mode" onchange="card(this.value);">
                      @if(Setting::get('CASH') == 1)
                        <option value="CASH">CASH</option>
                      @endif   
                      @if(Setting::get('CARD') == 1 || Setting::get('PAYSTACK') == 1)
                      @if($cards->count() > 0)
                        <option value="PAYSTACK">PAYSTACK</option>
                      @endif
                      @endif
                      
                    </select>
                    <br>

                    @if(Setting::get('CARD') == 1)
                        @if($cards->count() > 0)
                        <select class="form-control" name="card_id" style="display: none;" id="card_id">
                          <option value="">Select Card</option>
                          @foreach($cards as $card)
                            <option value="{{$card->card_id}}">{{$card->brand}} **** **** **** {{$card->last_four}}</option>
                          @endforeach
                        </select>
                        @endif
                    @endif

                    @if($fare->surge == 1)

                        <span><em>@lang('user.demand_node')</em></span>
                        <div class="surge-block"><span class="surge-text">{{$fare->surge_value}}</span>
                        </div>
                    
                    @endif
                    @if(Setting::get("CARD")==1 || Setting::get("CASH")==1)
                        <button type="submit" class="half-primary-btn fare-btn">@lang('user.ride.ride_now')</button>
                        <button type="button" class="half-secondary-btn fare-btn" data-toggle="modal" data-target="#schedule_modal">@lang('user.schedule')</button>
                    @endif

                </form>
            </div>

            <div class="col-md-6">
                <div class="user-request-map">
                    <?php 
                    $map_icon = asset('asset/img/marker-start.png');
                    $static_map = "https://maps.googleapis.com/maps/api/staticmap?autoscale=1&size=600x450&maptype=roadmap&format=png&visual_refresh=true&markers=icon:".$map_icon."%7C".$request->s_latitude.",".$request->s_longitude."&markers=icon:".$map_icon."%7C".$request->d_latitude.",".$request->d_longitude."&path=color:0x191919|weight:8|".$request->s_latitude.",".$request->s_longitude."|".$request->d_latitude.",".$request->d_longitude."&key=".Setting::get('map_key'); ?>
                    <div class="map-static" style="background-image: url({{$static_map}});">
                    </div>
                    <div class="from-to row no-margin">
                        <div class="from">
                            <h5>@lang('user.from')</h5>
                            <p>{{$request->s_address}}</p>
                        </div>
                        <div class="to">
                            <h5>@lang('user.to')</h5>
                            <p>{{$request->d_address}}</p>
                        </div>
                    </div>
                </div> 
            </div>
        </div>

    </div>
</div>



<!-- Schedule Modal -->
<div id="schedule_modal" class="modal fade schedule-modal" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">@lang('user.schedule_title')</h4>
      </div>
      <form>
      <div class="modal-body">
        
        <label>@lang('user.schedule_date')</label>
        <input value="{{date('m/d/Y')}}" type="text" id="datepicker" placeholder="Date" name="schedule_date">
        <label>@lang('user.schedule_time')</label>
        <input value="{{date('H:i')}}" type="text" id="timepicker" placeholder="Time" name="schedule_time">

      </div>
      <div class="modal-footer">
        <button type="button" id="schedule_button" class="btn btn-default" data-dismiss="modal">@lang('user.schedule_ride')</button>
      </div>

      </form>
    </div>

  </div>
</div>


@endsection

@section('scripts')
    <script type="text/javascript">
        $(document).ready(function(){
            $('#schedule_button').click(function(){
                $("#datepicker").clone().attr('type','hidden').appendTo($('#create_ride'));
                $("#timepicker").clone().attr('type','hidden').appendTo($('#create_ride'));
                document.getElementById('create_ride').submit();
            });
        });
    </script>
    <script type="text/javascript">
        var date = new Date();        
        date.setDate(date.getDate());
        $('#datepicker').datepicker({  
            startDate: date
        });
        $('#timepicker').timepicker({showMeridian : false});
    </script>
    <script type="text/javascript">
        @if(Setting::get('CASH') == 0)
            card('CARD');
        @endif
        function card(value){
            if(value == 'CARD'){
                $('#card_id').fadeIn(300);
            }else{
                $('#card_id').fadeOut(300);
            }
        }
       
        $('#promocode').on('change', function() {
          
          var estimate = {{$fare->estimated_fare}};
          var percentage = $('option:selected', this).attr('data-percent');
          var max_amount = $('option:selected', this).attr('data-max');
          var percent_total = estimate * percentage/100;
              if(percent_total > max_amount){
                promo = parseFloat(max_amount);
              }else{
                promo = parseFloat(percent_total);
              }
             $("#promo_amount").html("{{Setting::get('currency')}}"+promo.toFixed(2));
             $("#total_amount").html("{{Setting::get('currency')}}"+(estimate-promo).toFixed(2));
          });
    </script>
@endsection