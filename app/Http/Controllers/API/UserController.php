<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Stripe;
use Input;
use URL;
use Session;
use Stripe\Error\Card;

class UserController extends Controller
{

    public $successStatus = 200;

    /**
     * login api
     *
     * @return \Illuminate\Http\Response
     */
    public function login(){
        if(Auth::attempt(['email' => request('email'), 'password' => request('password')])){

            $user = Auth::user();

            $success['token'] =  $user->createToken('MyApp')->accessToken;
            $user->api_token = $success['token'];
            $user->save();

            $success['name'] =  $user->name;
            $success['email'] =  $user->email;

            return response()->json(['success' => $success], $this->successStatus);
        }
        else{
            return response()->json(['error'=>'Unauthorised'], 401);
        }
    }


    /**
     * Register api
     *
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);


        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);            
        }

        try {

                $input = $request->all();
                $input['password'] = bcrypt($input['password']);
                $user = User::create($input);
                $success['token'] =  $user->createToken('MyApp')->accessToken;
                $success['name'] =  $user->name;
                $success['email'] =  $user->email;

            $stripe = new \Stripe\StripeClient(
              'sk_test_cMQCopaPhv65n7yvavfRwg9L00WRdaOtag'
            );

            $dataStrip = $stripe->customers->create([
                  'id'   => 'cus_'.$user->id,
                  'name' => $user->name,
                  'email' => $user->email,  
                  'description' => 'Create New User '.$user->name,
                  'phone' => null,
                ]);


            return response()->json(['success'=>$success]); 

        } catch(\Stripe\Exception\CardException $e) {

         return response()->json(['error' => $e->getError()->type]);

        } 
   
    }


    /**
     * details api
     *    
     * @return \Illuminate\Http\Response
     */
    public function walletDetails(Request $request)
    {
        $user = Auth::user();

        try {

                $stripe = new \Stripe\StripeClient(
                  'sk_test_cMQCopaPhv65n7yvavfRwg9L00WRdaOtag'
                );


                $userWalletId = 'cus_'.$user->id;

                $walletDetails =  $stripe->customers->retrieve(
                      $userWalletId,
                      []    
                )->toArray();


            return response()->json(['success' =>  $walletDetails]);

        } catch(\Stripe\Exception\CardException $e) {

            return response()->json(['error' => $e->getError()->type]);

        } 

    }


    /**
     * details api
     *    
     * @return \Illuminate\Http\Response
    */

    public function amountAddWallet(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'amount' => 'required'
        ]);


        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()], 401);            
        }

       
         try {

            $user = Auth::user();

            $userWalletId = 'cus_'.$user->id;

            $stripe = new \Stripe\StripeClient(
                      'sk_test_cMQCopaPhv65n7yvavfRwg9L00WRdaOtag'
                    );


            $walletDetails =  $stripe->customers->retrieve(
                  $userWalletId,
                  []    
            )->toArray();


            $stripe = Stripe\Stripe::setApiKey('sk_test_cMQCopaPhv65n7yvavfRwg9L00WRdaOtag');

             $charge = Stripe\Charge::create ([
                     'customer' => $userWalletId,
                     'currency' => 'inr',
                     'amount' => $request->amount,
                     'description' => 'wallet',
                     'source' => $walletDetails['default_source']
                ])->toArray();

        return response()->json(['success' =>  $charge]);

        } catch(\Stripe\Exception\CardException $e) {

            return response()->json(['error' => $e->getError()->type]);

        } 
       
    }


}