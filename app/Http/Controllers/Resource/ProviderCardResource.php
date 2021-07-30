<?php

namespace App\Http\Controllers\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\User;
use App\ProviderCard;
use App\Provider;
use Exception;
use Auth;
use Setting;
use Session;

class ProviderCardResource extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try{

            $cards = ProviderCard::where('user_id',Auth::user()->id)->orderBy('created_at','desc')->get();
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
            "authorization:"."Bearer sk_test_e7be14bb1049a784f050c7595a6517687048cac5",
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
          // dd( $response);
          // 4084084084084081
        $response = json_decode($response,true);
        // dd($response['status']);
        //         $customer_id = $this->customer_id();
        //         $this->set_stripe();
        //         $customer = \Stripe\Customer::retrieve($customer_id);
        //         $card = $customer->sources->create(["source" => $request->stripe_token]);

        if($response['status']==true){
           $exist = ProviderCard::where('user_id',Auth::user()->id)
                                ->where('last_four',$response["data"]["authorization"]["last4"])
                                ->where('brand',$response["data"]["authorization"]["brand"])
                                ->count();
                                
                if($exist == 0){

                    $create_card = new ProviderCard;
                    $create_card->user_id = Auth::user()->id;
                    $create_card->card_id = $response["data"]["authorization"]["authorization_code"];
                    $create_card->last_four = $response["data"]["authorization"]["last4"];
                    $create_card->brand = $response["data"]["authorization"]["brand"];
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

   

    // public function store(Request $request)
    // {
    //     $this->validate($request,[
    //             'stripe_token' => 'required'
    //         ]);

    //     try{            
           
          
    //         $customer_id = $this->customer_id();
            
    //         $this->set_stripe();              

    //         $customer = \Stripe\Account::retrieve($customer_id);

    //         $card = $customer->external_accounts->create(
    //              array( "external_account" => $request->stripe_token )
    //         );
            
    //         $customer = \Stripe\Account::retrieve(Auth::user()->stripe_cust_id);


    //         $exist = ProviderCard::where('user_id',Auth::user()->id)
    //                         ->where('last_four',$card['last4'])
    //                         ->where('brand',$card['brand'])
    //                         ->count();

    //         if($exist == 0){
               
    //             //delete previous card
    //             $Providercard=ProviderCard::where('user_id',Auth::user()->id)->first();

    //             if(!empty($Providercard)){
    //                 $card_detail = $customer->external_accounts->retrieve($Providercard->card_id);

    //                 if(count($card_detail) > 1)
    //                 {
    //                     $card_detail->delete();
    //                 }

    //                 ProviderCard::where('card_id',$Providercard->card_id)->delete();
    //             }    

    //             //add new card
    //             $create_card = new ProviderCard;
    //             $create_card->user_id = Auth::user()->id;
    //             $create_card->card_id = $card['id'];
    //             $create_card->last_four = $card['last4'];
    //             $create_card->brand = $card['brand'];                
    //             $create_card->is_default = '1';
    //             $create_card->save();

    //         }else{
    //             if($request->ajax()){                   
    //                 return response()->json(['message' => trans('api.card_already')]); 
    //             }else{
    //                 return back()->with('flash_success',trans('api.card_already'));
    //             }
    //         }

    //         if($request->ajax()){
    //             return response()->json(['message' => trans('api.card_added')]); 
    //         }else{
    //             return back()->with('flash_success',trans('api.card_added'));
    //         }

    //     } catch(Exception $e){            
    //         if($request->ajax()){
    //             return response()->json(['error' => $e->getMessage()], 520);
    //         }else{               
    //             return back()->with('flash_error',$e->getMessage());
    //         }
    //     } 
    // }

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
        $this->validate($request,[
                'card_id' => 'required|exists:provider_cards,card_id,user_id,'.Auth::user()->id,
            ]);
        try{
            ProviderCard::where('user_id',Auth::user()->id)->update(['is_default' => '0']);
            ProviderCard::where('card_id',$request->card_id)->update(['is_default' => '1']);
            return 'success';
         }
         catch(Exception $e){
            return 'failure';
         }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {

        $this->validate($request,[
                'card_id' => 'required|exists:cards,card_id,user_id,'.Auth::user()->id,
            ]);

        try{


        $post = [
                  "authorization_code" => $request->card_id
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
            "authorization:"."Bearer sk_test_e7be14bb1049a784f050c7595a6517687048cac5",
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
            ProviderCard::where('card_id',$request->card_id)->delete();

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
    // public function destroy(Request $request)
    // {

    //     $this->validate($request,[
    //             'card_id' => 'required|exists:provider_cards,card_id,user_id,'.Auth::user()->id,
    //         ]);

    //     try{

    //        $this->set_stripe();

    //        $customer = \Stripe\Account::retrieve(Auth::user()->stripe_cust_id);

         
         
    //        $card_detail = $customer->external_accounts->retrieve($request->card_id);
    //        if(count($card_detail) > 1)

    //        {
    //          $card_detail->delete();
    //        }


    //        ProviderCard::where('card_id',$request->card_id)->delete();

    //         if($request->ajax()){
    //             return response()->json(['message' => trans('api.card_deleted')]); 
    //         }else{
    //             return back()->with('flash_success',trans('api.card_deleted'));
    //         }

    //     } catch(Stripe_CardError $e){           
    //         if($request->ajax()){
    //             return response()->json(['error' => $e->getMessage()], 500);
    //         }else{
    //             return back()->with('flash_error',$e->getMessage());
    //         }
    //     }
    // }

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

               // $customer = \Stripe\Customer::create([
                //     'email' => Auth::guard('provider')->user()->email,
                // ]);

               $customer= \Stripe\Account::create(array(
                    "country" => "US",
                    "type" => "custom",                    
                    "email" => Auth::user()->email
                ));

                $customer_update = \Stripe\Account::retrieve($customer['id']);
                $customer_update->tos_acceptance->date = time();
                $customer_update->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
                $customer_update->legal_entity->business_name =  Auth::user()->first_name.' '.Auth::user()->last_name; 
                $customer_update->legal_entity->dob->day = '27';
                $customer_update->legal_entity->dob->month = '05';
                $customer_update->legal_entity->dob->year= '1990';
                $customer_update->legal_entity->first_name = Auth::user()->first_name;
                $customer_update->legal_entity->last_name =Auth::user()->last_name;
                $customer_update->legal_entity->type = 'individual';
                $customer_update->save();

                Provider::where('id',Auth::user()->id)->update(['stripe_cust_id' => $customer['id']]);
                
                if(Setting::get('demo_mode', 0) == 1) {
                    Provider::where('id',Auth::user()->id)->where('status','card')->update(['status'=>'approved']);
                }
                else{
                    Provider::where('id',Auth::user()->id)->where('status','card')->update(['status'=>'onboarding']);
                }    

                return $customer['id'];

            } catch(Exception $e){

                   return $e;
            }
        }
    }

    public function set_default(Request $request)
    {
        try{
            ProviderCard::where('user_id',Auth::user()->id)->update(['is_default' => '0']);
            ProviderCard::where('id',$request->id)->update(['is_default' => '1']);
            return 'success';
         }
         catch(Exception $e){
            return 'failure';
         }
                   
    }

}
