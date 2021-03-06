<?php


namespace app\app\controller;

use think\Controller;
use Firebase\JWT\JWT;
use think\Exception;

class Base extends Controller
{
    public $prefix;
    public $redis_prefix;
    public $url;
    public $imgUrl;
    public $book_ctrl;
    public $uid;
    protected $end_point;

    protected function initialize()
    {
        parent::initialize();
        header("Access-Control-Allow-Origin:*");
        header("Access-Control-Allow-Methods:GET, POST, OPTIONS, DELETE");
        header("Access-Control-Allow-Headers:DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type, Accept-Language, Origin, Accept-Encoding");

        $token = $this->request->param('token');
        $time = $this->request->param('time');
        if (time() - $time > 180) {
            return json(['success' => 0, 'msg' => '过期请求'])->send();
            exit();
        }
        $key = config('site.api_key');
        if ($token != md5($key . $time)) {
            return json(['success' => 0, 'msg' => '未授权请求'])->send();
            exit();
        }

        $this->prefix = config('database.prefix');
        $this->redis_prefix = config('cache.prefix');
        $this->url = config('site.url');
        $this->imgUrl = config('site.img_site');
        $this->book_ctrl = BOOKCTRL;
    }

    public function getAuth($utoken){
        $key = config('site.api_key');
        try{
            $info = JWT::decode($utoken, $key, array('HS256', 'HS384', 'HS512', 'RS256' ));
            $arr = (array)$info;
            return json(['success' => 1, 'userInfo' => $arr]);
        } catch (\Exception $e) {
            return json(['success' => -1, 'msg' => $e->getMessage()]);
        }
    }
}