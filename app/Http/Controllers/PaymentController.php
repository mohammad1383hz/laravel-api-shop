<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Validator;

class PaymentController extends ApiController
{
    public function send(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'order_items' => 'required',
            'order_items.*.product_id' => 'required|integer',
            'order_items.*.quantity' => 'required|integer',
            'request_from' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }
        $totalAmount = 0;
        $deliveryAmount = 0;
        foreach ($request->order_items as $orderItem) {
            $product = Product::findOrFail($orderItem['product_id']);
            if ($product->quantity < $orderItem['quantity']) {
                return $this->errorResponse('The product quantity is incorrect', 422);
            }

            $totalAmount += $product->price * $orderItem['quantity'];
            $deliveryAmount += $product->delivery_amount;
        }

        $payingAmount = $totalAmount + $deliveryAmount;

        $amounts = [
            'totalAmount' => $totalAmount,
            'deliveryAmount' => $deliveryAmount,
            'payingAmount' => $payingAmount,
        ];

            $api = 'test';
            $amount = 100000;
            $mobile = '09394889350';
            $factorNumber = "شماره فاکتور";
            $description = "توضیحات";
            $redirect = env('redirect_payment');
            $result = $this->paysend($api, $amount, $redirect, $mobile, $factorNumber, $description);
            $result = json_decode($result);
            if ($result->status) {
                OrderController::create($request, $amounts, $result->token);
                $go = "https://pay.ir/pg/$result->token";
                return $this->successResponse([
                    'url' => $go
                ]);
            } else {
                return $this->errorResponse($result->errorMessage, 422);
            }
        }


    public function verify(Request $request){
                    $api = 'test';
            $token = $request->token;
            // dd($token);
            $result = json_decode($this->payverify($api,$token));

            return response()->json($result);
            if (isset($result->status)) {
                if ($result->status == 1) {
                    if(Transaction::where('trans_id' , $result->transId)->exists()){
                        return $this->errorResponse('این تراکنش قبلا توی سیستم ثبت شده است' , 422);
                    }
                    OrderController::update($token, $result->transId);
                    return $this->successResponse('تراکنش با موفقیت انجام شد' , 200);
                } else {
                    return $this->errorResponse('تراکنش با خطا مواجه شد' , 422);
                }
            } else {
                if ($request->status == 0) {
                    return $this->errorResponse('تراکنش با خطا مواجه شد' , 422);
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
