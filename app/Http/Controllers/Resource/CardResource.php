<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use App\Card;
use Exception;
use Auth;
use Setting;

class CardResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{

            $cards = Card::where('user_id',Auth::user()->id)->orderBy('created_at','desc')->get();
            return $cards; 

        } catch(Exception $e){
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //  
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
   public function store(Request $request)
    {
         // dd($request->all());
        $this->validate($request,[
                // 'stripe_token' => 'required'
                // 'name' => 'required',
                'cvv' => 'required',
                'number' => 'required',
                'exp_month' => 'required',
                'exp_year' => 'required'
            ]);

        try{
        $post = [
                  "email" => Auth::user()->email,
                  "amount" => '1', // 1 according to payment gateway
                  "metadata" => ["custom_fields" => [
                                "value"=> Auth::user()->first_name.' '.Auth::user()->last_name,
                                "display_name" => 'Wallet Recharge',
                                "variable_name" => "wallet_recharge"
                                ]],
                  "card" => ["cvv" => $request->cvv,
                            "number" => $request->number,
                            "expiry_month" => $request->exp_month,
                            "expiry_year" => $request->exp_year,
                            ],
                  "pin" => "0000"
                ];
          $post = json_encode($post);
       
          $curl = curl_init();
          curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.paystack.co/charge",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $post,
          CURLOPT_HTTPHEADER => array(
            "authorization:"."Bearer sk_live_b5a1b44db1ee251a889f56695abc31343b2f5ed5",
            "content-type: application/json"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            if($request->ajax()){
                return response()->json(['error' => $err->getMessage()], 500);
            }else{
                return back()->with('flash_error',$err->getMessage());
            }
        }
          // dd( $response);
          // 4084084084084081
        $response = json_decode($response,true);
        // dd($response['status']);
        //         $customer_id = $this->customer_id();
        //         $this->set_stripe();
        //         $customer = \Stripe\Customer::retrieve($customer_id);
        //         $card = $customer->sources->create(["source" => $request->stripe_token]);
        \Log::info($response);
        if($response['status']==true){
           $exist = Card::where('user_id',Auth::user()->id)
                                ->where('last_four',substr($request->number,-4))
                                ->count();
                                
                if($exist == 0){

                    $create_card = new Card;
                    $create_card->user_id = Auth::user()->id;
                    $create_card->card_id = $response["data"]["reference"];
                    $create_card->last_four =substr($request->number,-4);
                    //$create_card->brand = $response["data"]["authorization"]["brand"];
                    $create_card->save();

                }else{
                    return response()->json(['message' => 'Card Already Added']); 
                }
        }else{
           if($request->ajax()){
                return response()->json(['error' => 'invalid card' ], 500);
            }else{
                return back()->with('flash_error','invalid card');
            }
        }
               

            if($request->ajax()){
                return response()->json(['message' => 'Card Added']); 
            }else{
                return back()->with('flash_success','Card Added');
            }

        } catch(Exception $e){
            dd($e);
            if($request->ajax()){
                return response()->json(['error' => $e->getMessage()], 500);
            }else{
                return back()->with('flash_error',$e->getMessage());
            }
        } 
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
\Log::info($request->all());
        $this->validate($request,[
                'card_id' => 'required|exists:cards,card_id,user_id,'.Auth::user()->id,
            ]);

        try{


        $post = [
                  "reference" => $request->card_id
                ];
          $post = json_encode($post);
       
          $curl = curl_init();
          curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api.paystack.co/customer/deactivate_authorization",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $post,
          CURLOPT_HTTPHEADER => array(
            "authorization:"."Bearer sk_live_b5a1b44db1ee251a889f56695abc31343b2f5ed5",
            "content-type: application/json"
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          return $err->getMessage();
            if($request->ajax()){
                return response()->json(['error' => $err->getMessage()], 500);
            }else{
                return back()->with('flash_error',$err->getMessage());
            }
        }

        $response = json_decode($response,true);

        if($response['status']=='true') {
            Card::where('card_id',$request->card_id)->delete();

            if($request->ajax()){
                return response()->json(['message' => 'Card Deleted']); 
            }else{
                return back()->with('flash_success','Card Deleted');
            }
        } else {
            if($request->ajax()){
                return response()->json(['error' => 'Card cannot be deleted'], 500);
            }else{
                return back()->with('flash_error','Card cannot be deleted');
            }
        }

        } catch(Exception $e){
            if($request->ajax()){
                return response()->json(['error' => $e->getMessage()], 500);
            }else{
                return back()->with('flash_error',$e->getMessage());
            }
        }
    }

    /**
     * setting stripe.
     *
     * @return \Illuminate\Http\Response
     */
    public function set_stripe(){
        return \Stripe\Stripe::setApiKey(Setting::get('stripe_secret_key'));
    }

    /**
     * Get a stripe customer id.
     *
     * @return \Illuminate\Http\Response
     */
    public function customer_id()
    {
        if(Auth::user()->stripe_cust_id != null){

            return Auth::user()->stripe_cust_id;

        }else{

            try{

                $stripe = $this->set_stripe();

                $customer = \Stripe\Customer::create([
                    'email' => Auth::user()->email,
                ]);

                User::where('id',Auth::user()->id)->update(['stripe_cust_id' => $customer['id']]);
                return $customer['id'];

            } catch(Exception $e){
                return $e;
            }
        }
    }

}
