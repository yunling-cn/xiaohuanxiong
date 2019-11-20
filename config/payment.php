<?php
return [
    'zhapay' => [ //幻兮支付，官网地址:https://www.zhapay.com/
        'appid' => '',
        'appkey' => '',
        'channel' => [
            ['type' => 2, 'code' => 1, 'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 1, 'code' => 3, 'img' => 'weixin', 'title' => '微信支付']
        ]
    ],
    'sevenpay' => [ //7c支付，官网地址：https://www.7cpo.com/
        'token' => '',
        'businessId' => '',
        'channel' => [
            ['type' => 0, 'code' => 1, 'img' => 'alipay', 'title' => '支付宝'],
            ['type' => 1, 'code' => 3, 'img' => 'weixin', 'title' => '微信支付']
        ]
    ],
    'paypal' => [
        'clientId' => '',
        'clientSecret' => '',
        'channel' => [
            ['type' => 'paypal', 'code' => 5, 'img' => 'paypal', 'title' => '贝宝支付'],
        ]
    ],
    'kami' => [
        'url' => 'https://t.cn/AiTUbhWs' //卡密地址
    ],
    'vip' => [  //设置vip天数及相应的价格
        ['month' => 1, 'price' => 5],
        ['month' => 6, 'price' => 100],
        ['month' => 12, 'price' => 400]
    ],
    'money' => [1, 5, 10, 30, 50], //设置支付金额
    'promotional_rewards_rate' => 0.1, //设置充值提成比例，必须是小数
    'reg_rewards' => 1 //注册奖励金额，单位是元
];