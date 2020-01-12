<?php


namespace Util;
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

class Paypal
{
    public function submit($order_id, $money, $pay_type)
    {
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
        $price=$money;// 总价
        $shipping=0;// 运费
        $Currency='USD'; //币种 美元
        $product=config('site.site_name') . '充值';
        $description=config('site.site_name') . '充值';
        $total = $price + $shipping;//总价
        $accept_url = config('site.url') . '/Paypalnotify';//返回地址
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item = new Item();
        $item->setName($product)->setCurrency($Currency)->setQuantity(1)->setPrice($price);

        $itemList = new ItemList();
        $itemList->setItems([$item]);

        $details = new Details();
        $details->setShipping($shipping)->setSubtotal($price);

        $amount = new Amount();
        $amount->setCurrency($Currency)->setTotal($total)->setDetails($details);

        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList)->setDescription($description)->setInvoiceNumber($order_id);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($accept_url .'?success=true&out_trade_no='.$order_id.'&total_fee='.$total)->setCancelUrl($accept_url .'?success=false');

        $payment = new Payment();
        $payment->setIntent('sale')->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions([$transaction]);
        try {
            $payment->create($paypal);
        } catch (PayPalConnectionException $e) {
            echo $e->getData();
            die();
        }

        $approvalUrl = $payment->getApprovalLink();
        echo $approvalUrl;
        header("Location: {$approvalUrl}");die;
    }
}

