<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;

class PaymentController extends ApiController
{
    public function send(){

            $api = 'test';
            $amount = 100000;
            $mobile = '09394889350';
            $factorNumber = "شماره فاکتور";
            $description = "توضیحات";
            $redirect = env('redirect_payment');
            $result = $this->paysend($api, $amount, $redirect, $mobile, $factorNumber, $description);
            $result = json_decode($result);
            if($result->status) {
                $go = "https://pay.ir/pg/$result->token";
                return $this->succesresponse([
                    'url'=>$go
                ]);
            } else {
                echo $result->errorMessage;
                return $this->errorresponse($result->errorMassage,422);
            }
    }


    public function verify(Request $request){
                    $api = 'test';
            $token = $request->token;
            // dd($token);
            $result = json_decode($this->payverify($api,$token));

            return response()->json($result);
            if(isset($result->status)){
                if($result->status == 1){
                    echo "<h1>تراکنش با موفقیت انجام شد</h1>";
                } else {
                    echo "<h1>تراکنش با خطا مواجه شد</h1>";
                }
            } else {
                if($_GET['status'] == 0){
                    echo "<h1>تراکنش با خطا مواجه شد</h1>";
                }
            }
    }

    public function paysend($api, $amount, $redirect, $mobile = null, $factorNumber = null, $description = null) {
        return $this->curl_post('https://pay.ir/pg/send', [
            'api'          => $api,
            'amount'       => $amount,
            'redirect'     => $redirect,
            'mobile'       => $mobile,
            'factorNumber' => $factorNumber,
            'description'  => $description,
        ]);
    }

   public function curl_post($url, $params)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
	]);
	$res = curl_exec($ch);
	curl_close($ch);

	return $res;
}
public function payverify($api, $token) {
	return $this->curl_post('https://pay.ir/pg/verify', [
		'api' 	=> $api,
		'token' => $token,
	]);
}

}
