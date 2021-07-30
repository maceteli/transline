<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SendPushNotification;

use Stripe\Charge;
use Stripe\Stripe;
use Stripe\StripeInvalidRequestError;

use Auth;
use Setting;
use Exception;
use Paystack;
use App\Card;
use App\User;
use App\WalletPassbook;
use App\UserRequests;
use App\UserRequestPayment;
use App\WalletRequests;
use App\Provider;
use App\Fleet;
use App\PaystackDetails;

use App\Http\Controllers\ProviderResources\TripController;
// pk_test_5uaXGwSAdTquzMmSY7tycpzF
// sk_test_xkxuwLlMe1FVw8r2fKGvysnn
class PaymentController extends Controller
{
       /**
     * payment for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function payment(Request $request)
    {
      

        $this->validate($request, [
                'request_id' => 'required|exists:user_request_payments,request_id|exists:user_requests,id,paid,0,user_id,'.Auth::user()->id
            ]);


        $UserRequest = UserRequests::find($request->request_id);
        
        $tip_amount=0;

        if($UserRequest->payment_mode == 'CARD') {

            $RequestPayment = UserRequestPayment::where('request_id',$request->request_id)->first(); 
            
            if(isset($request->tips) && !empty($request->tips)){
                $tip_amount=round($request->tips,2);
            }
            
            $StripeCharge = ($RequestPayment->payable+$tip_amount) * 100;
            
           
            try {

                $Card = Card::where('user_id',Auth::user()->id)->where('is_default',1)->first();
                $stripe_secret = Setting::get('stripe_secret_key');

                Stripe::setApiKey(Setting::get('stripe_secret_key'));
                
                if($StripeCharge  == 0){

                $RequestPayment->payment_mode = 'CARD';
                $RequestPayment->card = $RequestPayment->payable;
                $RequestPayment->payable = 0;
                $RequestPayment->tips = $tip_amount;                
                $RequestPayment->provider_pay = $RequestPayment->provider_pay+$tip_amount;
                $RequestPayment->save();

                $UserRequest->paid = 1;
                $UserRequest->status = 'COMPLETED';
                $UserRequest->save();

                //for create the transaction
                (new TripController)->callTransaction($request->request_id);

                if($request->ajax()) {
                   return response()->json(['message' => trans('api.paid')]); 
                } else {
                    return redirect('dashboard')->with('flash_success', trans('api.paid'));
                }
            }else{
                
                $Charge = Charge::create(array(
                      "amount" => $StripeCharge,
                      "currency" => "usd",
                      "customer" => Auth::user()->stripe_cust_id,
                      "card" => $Card->card_id,
                      "description" => "Payment Charge for ".Auth::user()->email,
                      "receipt_email" => Auth::user()->email
                    ));

                /*$ProviderCharge = (($RequestPayment->total+$RequestPayment->tips - $RequestPayment->tax) - $RequestPayment->commision) * 100;

                $transfer = Transfer::create(array(
                    "amount" => $ProviderCharge,
                    "currency" => "usd",
                    "destination" => $Provider->stripe_acc_id,
                    "transfer_group" => "Request_".$UserRequest->id,
                  )); */    
                 
                $RequestPayment->payment_id = $Charge["id"];
                $RequestPayment->payment_mode = 'CARD';
                $RequestPayment->card = $RequestPayment->payable;
                $RequestPayment->payable = 0;
                $RequestPayment->tips = $tip_amount;
                $RequestPayment->provider_pay = $RequestPayment->provider_pay+$tip_amount;
                $RequestPayment->save();

                $UserRequest->paid = 1;
                $UserRequest->status = 'COMPLETED';
                $UserRequest->save();

                //for create the transaction
                (new TripController)->callTransaction($request->request_id);

                if($request->ajax()) {
                   return response()->json(['message' => trans('api.paid')]); 
                } else {
                    return redirect('dashboard')->with('flash_success', trans('api.paid'));
                }
              }

            } catch(StripeInvalidRequestError $e){
              
                if($request->ajax()){
                    return response()->json(['error' => $e->getMessage()], 500);
                } else {
                    return back()->with('flash_error', $e->getMessage());
                }
            } catch(Exception $e) {
                if($request->ajax()){
                    return response()->json(['error' => $e->getMessage()], 500);
                } else {
                    return back()->with('flash_error', $e->getMessage());
                }
            }
        } else if($UserRequest->payment_mode == 'PAYSTACK') {

            $RequestPayment = UserRequestPayment::where('request_id',$request->request_id)->first(); 
            
            $RequestCharge = $RequestPayment->total * 100;
           
            try {

                $Card = Card::where('user_id',Auth::user()->id)->where('is_default',1)->first();
                
                if($RequestCharge  == 0){

                $RequestPayment->payment_mode = 'PAYSTACK';
                $RequestPayment->save();

                $UserRequest->paid = 1;
                $UserRequest->status = 'COMPLETED';
                $UserRequest->save();


                   if($request->ajax()) {
                   return response()->json(['message' => trans('api.paid')]); 
                } else {
                    return redirect('dashboard')->with('flash_success','Paid');
                }
               }else{
                
               // $Charge = Charge::create(array(
               //        "amount" => $RequestCharge,
               //        "currency" => "usd",
               //        "customer" => Auth::user()->stripe_cust_id,
               //        "card" => $Card->card_id,
               //        "description" => "Payment Charge for ".Auth::user()->email,
               //        "receipt_email" => Auth::user()->email
               //      ));
                 
              $post = [
                      "email" => Auth::user()->email,
                      "amount" => $RequestCharge, 
                      "authorization_code" => $Card->card_id
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
                return $err->getMessage();
                  if($request->ajax()){
                      return response()->json(['error' => $err->getMessage()], 500);
                  }else{
                      return back()->with('flash_error',$err->getMessage());
                  }
              }

              $response = json_decode($response,true);
              \Log::info($response);
                $RequestPayment->payment_id = $response['data']['reference'];
                $RequestPayment->payment_mode = 'PAYSTACK';
                $RequestPayment->save();

                $UserRequest->paid = 1;
                $UserRequest->status = 'COMPLETED';
                $UserRequest->save();

                if($request->ajax()) {
                   return response()->json(['message' => trans('api.paid')]); 
                } else {
                    return redirect('dashboard')->with('flash_success','Paid');
                }
              }

            } catch(Exception $e) {
                if($request->ajax()){
                    return response()->json(['error' => 'Something went wrong.'], 500);
                } else {
                    return back()->with('flash_error', $e->getMessage());
                }
            }
        }
    }


    /**
     * add wallet money for user.
     *
     * @return \Illuminate\Http\Response
     */
   public function add_money(Request $request){
\Log::info($request->all());
      if($request->card_id != 'CC_AVENUE')
      {
          $this->validate($request, [
                  'amount' => 'required|integer',
                  'card_id' => 'required|exists:cards,card_id,user_id,'.Auth::user()->id
              ],['card_id.required'=>'Choose any card to procced']);

          try{


              $WalletCharge = $request->amount*100;

              // Stripe::setApiKey(Setting::get('stripe_secret_key'));

              // $Charge = Charge::create(array(
              //       "amount" => $StripeWalletCharge,
              //       "currency" => "usd",
              //       "customer" => Auth::user()->stripe_cust_id,
              //       "card" => $request->card_id,
              //       "description" => "Adding Money for ".Auth::user()->email,
              //       "receipt_email" => Auth::user()->email
              //     ));

              $post = [
                      "email" => Auth::user()->email,
                      "amount" => $WalletCharge, 
                      "reference" => $request->card_id
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
                \Log::info('ee');
               // return $err->getMessage();
                  if($request->ajax()){
                      return response()->json(['error' => $err->getMessage()], 500);
                  }else{
                      return back()->with('flash_error',$err->getMessage());
                  }
              }

              $response = json_decode($response,true);

              \Log::info($response);
              $update_user = User::find(Auth::user()->id);
              $update_user->wallet_balance += $request->amount;
              $update_user->save();
                              \Log::info($response["status"]);
              if($response["status"]==true) {
              WalletPassbook::create([
                'user_id' => Auth::user()->id,
                'amount' => $request->amount,
                'status' => 'CREDITED',
                'via' => 'CARD',
              ]);

              Card::where('user_id',Auth::user()->id)->update(['is_default' => 0]);
              Card::where('card_id',$request->card_id)->update(['is_default' => 1]);

              //sending push on adding wallet money
              (new SendPushNotification)->WalletMoney(Auth::user()->id,currency($request->amount));

              if($request->ajax()){
                  return response()->json(['message' => currency($request->amount).trans('api.added_to_your_wallet'), 'user' => $update_user]); 
              } else {
                  return redirect('wallet')->with('flash_success',currency($request->amount).' added to your wallet');
              }
              } else {
              if($request->ajax()) {
                  return response()->json(['error' => "Something Went Wrong"], 500);
              } else {
                  return back()->with('flash_error', "Something Went Wrong");
              }
              } 

          // } catch(StripeInvalidRequestError $e) {
          //     if($request->ajax()){
          //          return response()->json(['error' => $e->getMessage()], 500);
          //     }else{
          //         return back()->with('flash_error',$e->getMessage());
          //     }
          } catch(Exception $e) {
            \Log::info($e);
              if($request->ajax()) {
                  return response()->json(['error' => $e->getMessage()], 500);
              } else {
                  return back()->with('flash_error', $e->getMessage());
              }
          }
      }
      else
      {
          $tid=rand(10,10000000);

         /* All Required Parameters by your Gateway */
      
          $parameters = [
          
            'tid' => $tid,
            
            'order_id' => 'wallet-'.Auth::user()->id,
            
            'amount' => $request->amount,
            
          ];
          
          // gateway = CCAvenue / PayUMoney / EBS / Citrus / InstaMojo / ZapakPay / Mocker
          
          $order = Indipay::gateway('CCAvenue')->prepare($parameters);
          return Indipay::process($order);
      }
    }


    /**
     * send money to provider or fleet.
     *
     * @return \Illuminate\Http\Response
     */
    public function send_money(Request $request, $id){
            
        try{

            $Requests = WalletRequests::where('id',$id)->first();

            if($Requests->request_from=='provider'){
              $provider = Provider::find($Requests->from_id);
              $stripe_cust_id=$provider->stripe_cust_id;
              $email=$provider->email;
            }
            else{
              $fleet = Fleet::find($Requests->from_id);
              $stripe_cust_id=$fleet->stripe_cust_id;
              $email=$fleet->email;
            }

            if(empty($stripe_cust_id)){              
              throw new Exception(trans('admin.payment_msgs.account_not_found'));              
            }

            $StripeCharge = $Requests->amount * 100;

            Stripe::setApiKey(Setting::get('stripe_secret_key'));

            $tranfer = \Stripe\Transfer::create(array(
                     "amount" => $StripeCharge,
                     "currency" => "usd",
                     "destination" => $stripe_cust_id,
                     "description" => "Payment Settlement for ".$email                     
                 ));           

            //create the settlement transactions
            (new TripController)->settlements($id);

             $response=array();
            $response['success']=trans('admin.payment_msgs.amount_send');
           
        } catch(Exception $e) {
            $response['error']=$e->getMessage();           
        }

        return $response;
    }


     public function redirectToGateway(Request $request)

    {  
         // dd($request->request_id);
         $det = new PaystackDetails();
         $det->reference = $request->reference;
         $det->request_id = $request->request_id;
         $det->user_id = Auth::user()->id;
         $det->save();
      
        return Paystack::getAuthorizationUrl()->redirectNow();
    }

    public function handleGatewayCallback(Request $request)
    {
        $paymentDetails = Paystack::getPaymentData();

      

        if($paymentDetails){

          $paystackdetail = PaystackDetails::where('reference',$paymentDetails['data']['reference'])->first();
       
          $paystackdetail->paystack_id = $paymentDetails['data']['id'];
          $paystackdetail->amount =  $paymentDetails['data']['amount'];
          $paystackdetail->currency =  $paymentDetails['data']['currency'];
          $paystackdetail->transaction_date =  $paymentDetails['data']['transaction_date'];
          $paystackdetail->status =  $paymentDetails['data']['status'];
          $paystackdetail->reference = $paymentDetails['data']['reference'];
          $paystackdetail->domain =  $paymentDetails['data']['domain'];
          $paystackdetail->gateway_response =  $paymentDetails['data']['gateway_response'];
          $paystackdetail->message =  $paymentDetails['data']['message'];
          $paystackdetail->channel =  $paymentDetails['data']['channel'];
          $paystackdetail->save();
                   
            if($paymentDetails['data']['status']=='success'){
           
              if($paystackdetail->request_id!=0){
                $UserRequest = UserRequests::find($paystackdetail->request_id);

                $RequestPayment = UserRequestPayment::where('request_id',$UserRequest->id)->first(); 

                $amount = $paystackdetail->amount/100;

                $tip_amount = $amount - $RequestPayment->payable;

                $RequestPayment->payment_id = $paymentDetails['data']['id'];
                $RequestPayment->payment_mode = 'CARD';
                $RequestPayment->card = $RequestPayment->payable;
                $RequestPayment->payable = 0;
                $RequestPayment->tips = $tip_amount;
                $RequestPayment->provider_pay = $RequestPayment->provider_pay+$tip_amount;
                $RequestPayment->save();

                $UserRequest->paid = 1;
                $UserRequest->status = 'COMPLETED';
                $UserRequest->save();

                //for create the transaction
                (new TripController)->callTransaction($UserRequest->id);

                if($request->ajax()) {
                   return response()->json(['message' => trans('api.paid')]); 
                } else {
                    return redirect('dashboard')->with('flash_success', trans('api.paid'));
                }
                 
              }else{

                   $amount = $paystackdetail->amount/100;
                      (new SendPushNotification)->WalletMoney(Auth::user()->id,currency($amount));

            //for create the user wallet transaction
                      (new TripController)->userCreditDebit($amount,Auth::user()->id,1);

                      $wallet_balance=Auth::user()->wallet_balance+$amount;

                      if($request->ajax()){
                          return response()->json(['success' => currency($amount)." ".trans('api.added_to_your_wallet'), 'message' => currency($amount)." ".trans('api.added_to_your_wallet'), 'balance' => $wallet_balance]); 
                      } else {
                          return redirect('wallet')->with('flash_success',currency($amount).trans('admin.payment_msgs.amount_added'));
                      }

              }

            }else{

                  return redirect('dashboard')->with('flash_error', 'Payment Failed');

            }

         

        }
        
    }


        public function providerhandleGatewayCallback(Request $request)
    {
        $paymentDetails = Paystack::getPaymentData();


        if($paymentDetails){

          $paystackdetail = PaystackDetail::where('reference',$paymentDetails['data']['reference'])->firstOrFail();
       
          $paystackdetail->paystack_id = $paymentDetails['data']['id'];
          $paystackdetail->amount =  $paymentDetails['data']['amount'];
          $paystackdetail->currency =  $paymentDetails['data']['currency'];
          $paystackdetail->transaction_date =  $paymentDetails['data']['transaction_date'];
          $paystackdetail->status =  $paymentDetails['data']['status'];
          $paystackdetail->reference = $paymentDetails['data']['reference'];
          $paystackdetail->domain =  $paymentDetails['data']['domain'];
          $paystackdetail->gateway_response =  $paymentDetails['data']['gateway_response'];
          $paystackdetail->message =  $paymentDetails['data']['message'];
          $paystackdetail->channel =  $paymentDetails['data']['channel'];
          $paystackdetail->save();
                  
            if($paymentDetails['data']['status']=='success'){
           
              
                  $amount = $paystackdetail->amount/100;
                    
                  $wallet_balance=Auth::user()->wallet_balance+$amount;

                  \Log::info("After Adding Wallet amount---".$wallet_balance);

                  $transaction_alias = mt_rand('11111','99999');
                  $ipdata=array();
                  $ipdata['transaction_id']=$transaction_alias;
                  $ipdata['transaction_alias']=$transaction_alias;
                  $ipdata['transaction_desc']="Wallet Recharge";
                  $ipdata['transaction_type']=4;        
                  $ipdata['type']='C';
                  $ipdata['amount']=$amount;
                  $this->createAdminWallet($ipdata);

                  
                  $ipdata=array();
                  $ipdata['transaction_id']=$transaction_alias;
                  $ipdata['transaction_alias']=$transaction_alias;
                  $ipdata['transaction_desc']="Wallet Recharge";
                  $ipdata['id']=Auth::user()->id;        
                  $ipdata['type']='c';
                  $ipdata['amount']=$amount;
                  $ipdata['payment_mode']='CARD';
                  $this->createProviderWallet($ipdata);

                  (new SendPushNotification)->ProviderWalletMoney(Auth::user()->id,currency($amount));

                if($request->ajax()){
                    return response()->json(['success' => currency($amount)." ".trans('api.added_to_your_wallet'), 'message' => currency($amount)." ".'added to your wallet', 'balance' => $wallet_balance]); 
                } else {
                    return redirect('/provider/wallet_transation')->with('flash_success',currency($amount).'added to your wallet');
                }




  

            }else{

                  return redirect('dashboard')->with('flash_error', 'Payment Failed');

            }

         

        }
        
    }



      public function paystack_status(Request $request)

    {   

       if($request->reference != ''){
         $det = new PaystackDetails();
         $det->reference = $request->reference;
         $det->request_id = $request->request_id;
         

         $det->user_id = Auth::user()->id;
         $det->save();


         $UserRequest = UserRequests::find($request->request_id);
         if($UserRequest){
                $RequestPayment = UserRequestPayment::where('request_id',$UserRequest->id)->first(); 

                

                $RequestPayment->payment_id = $request->reference;
                $RequestPayment->payment_mode = 'CARD';
                $RequestPayment->save();

                $UserRequest->paid = 1;
                $UserRequest->status = 'COMPLETED';
                $UserRequest->save();

    
                if($request->ajax()) {
                   return response()->json(['message' => trans('api.paid')]); 
                } else {
                    return redirect('dashboard')->with('flash_success', trans('api.paid'));
                }

            }

       }
       
    }

    public function paystack__wallet_status(Request $request)

    {   

       if($request->reference != ''){
             $det = new PaystackDetail();
             $det->reference = $request->reference;
             $det->request_id = $request->request_id;
             $det->amount  = $request->amount;
             $det->user_id = Auth::user()->id;
             $det->save();

            (new SendPushNotification)->WalletMoney(Auth::user()->id,currency($request->amount));

            //for create the user wallet transaction
            (new TripController)->userCreditDebit($request->amount,Auth::user()->id,1);

            $wallet_balance=Auth::user()->wallet_balance+$request->amount;

            if($request->ajax()){
                return response()->json(['success' => currency($request->amount)." ".trans('api.added_to_your_wallet'), 'message' => currency($request->amount)." ".trans('api.added_to_your_wallet'), 'balance' => $wallet_balance]); 
            } else {
                return redirect('wallet')->with('flash_success',currency($request->amount).trans('admin.payment_msgs.amount_added'));
            }


         

       }else{

              if($request->ajax()){
                      return response()->json(['message' => 'Error']); 
                  } else {
                      return redirect('wallet');
                  }
       }
       
    }
}
