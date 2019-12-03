<?php


namespace app\app\controller;


use Firebase\JWT\JWT;
use think\Exception;

class BaseAuth extends Base
{
    public $uid;

    protected function initialize()
    {
        parent::initialize();
        $key = config('site.api_key');
        $header = $this->request->header();
        if (isset($header['utoken'])) {
            $utoken = $header['utoken'];
            try{
                $info = JWT::decode($utoken, $key);
                $this->uid = $info['uid'];
            } catch (Exception $e) {
                return json(['success' => 0, 'msg' => $e->getMessage()])->send();
            }
        } else {
            return json(['success' => 0, 'msg' => '用户未登录'])->send();
        }
    }
}