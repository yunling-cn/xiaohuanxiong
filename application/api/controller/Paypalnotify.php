<?php


namespace app\api\controller;


use app\model\UserFinance;
use app\model\UserOrder;
use app\service\PromotionService;
use think\Controller;
use think\facade\Cache;
use think\Request;
use PayPal\Api\Payer;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Details;
use PayPal\Api\Amount;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Payment;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Rest\ApiContext;
use PayPal\Api\PaymentExecution;

class Paypalnotify extends Controller
{
    public function index(Request $request)
    {
        $data = $request->param();
        $clientId=trim(config('payment.paypal.clientId'));
        $clientSecret=trim(config('payment.paypal.clientSecret'));
        //echo $clientId."#####".$clientSecret;die();
        $paypal = new ApiContext(
            new OAuthTokenCredential(
                $clientId,
                $clientSecret
                )
        );
        //如果是沙盒测试环境不设置，请注释掉
        $paypal->setConfig(
            array(
                'mode' => 'live',
                )
            );
        // 修改订单状态
        $success = trim($data['success']);

        if ($success == 'false' && !isset($data['paymentId']) && !isset($data['PayerID'])) {
                echo "<script>alert('取消支付。');
                history.go(-4);
            </script>";
            exit();
        }

        $paymentId = trim($data['paymentId']);
        $PayerID = trim($data['PayerID']);

        if (!isset($success, $paymentId, $PayerID)) {
            echo 'Failure to pay。';
            exit();
        }

        if ((bool)$data['success'] === 'false') {
            echo 'Failure to pay，payment ID【' . $paymentId . '】,Payer ID【' . $PayerID . '】';
            exit();
        }

        $payment = Payment::get($paymentId, $paypal);

        $execute = new PaymentExecution();

        $execute->setPayerId($PayerID);

        try {
          $payment->execute($execute, $paypal);
         } catch (Exception $e) {
            echo $e . ',支付失败，支付ID【' . $paymentId . '】,支付人ID【' . $PayerID . '】';
            exit();
        }
 
        // 到这里就支付成功了，可以修改订单状态，需要自己传参数，可以在成功回调地址后面加
        // code....
        //业务处理               
        $order_id = str_replace('xwx_order_', '', $data['out_trade_no']); //收取订单号
        $status=1;
        $order = UserOrder::get($order_id); //通过返回的订单id查询数据库
        //print_r(var_export($order,true));die; 
        if ($order) {
            if ((int)$order->status == 0){
                $order->money = $data['total_fee'];
                $order->update_time = time(); //云端处理订单时间戳
                $order->status = $status;
                $order->isupdate(true)->save(); //更新订单

                $userFinance = new UserFinance();
                $userFinance->user_id = $order->user_id;
                $userFinance->money = $order->money;
                $userFinance->usage = 1; //用户充值
                $userFinance->summary = 'Paypal';
                $userFinance->save(); //存储用户充值数据

                $promotionService = new PromotionService();
                $promotionService->rewards($order->user_id, $order->money, 1); //调用推广处理函数
            }
        }        
        // 可以跳转订单页面
        $url = 'https://www.chrisdream.com/ucenter';
        header("Location:$url");die;
    }
}