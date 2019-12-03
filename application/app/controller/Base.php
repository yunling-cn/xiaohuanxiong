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

    protected function initialize()
    {
        parent::initialize();
        $token = $this->request->param('token');
        $time = $this->request->param('time');
        if (time() - $time > 180) {
            return json(['success' => 0, 'msg' => '过期请求'])->send();
        }
        $key = config('site.api_key');
        if ($token != md5($key . $time)) {
            return json(['success' => 0, 'msg' => '未授权请求'])->send();
        }

        $this->prefix = config('database.prefix');
        $this->redis_prefix = config('cache.prefix');
        $this->url = config('site.url');
        $this->imgUrl = config('site.img_site');
        $this->book_ctrl = BOOKCTRL;
    }
}