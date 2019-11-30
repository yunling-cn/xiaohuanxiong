<?php


namespace app\app\controller;

use think\App;
use think\Controller;
use think\facade\Response;
use think\Request;

class Base extends Controller
{
    public $prefix;
    public $redis_prefix;
    public $uid;
    public $url;
    public $imgUrl;
    public $book_ctrl;
    protected $isLogin;

    protected function initialize()
    {
        $token = $this->request->param('token');
        $time = $this->request->param('time');
        if (time() - $time > 180) {
            echo json_encode(['success' => 0, 'msg' => '过期请求']);
            exit();
        }
        $key = config('site.api_key');
        if ($token != md5($key . $time)) {
            echo json_encode(['success' => 0, 'msg' => '未授权请求']);
            exit();
        }

        $this->prefix = config('database.prefix');
        $this->redis_prefix = config('cache.prefix');
        $this->url = config('site.url');
        $this->imgUrl = config('site.img_site');
        $this->book_ctrl = BOOKCTRL;
    }

    public function checkAuth(Request $request)
    {
        $utoken = $request->param('utoken');
        $uid = $request->param('uid');
        $redis = new_redis();
        $utoken_in_redis = $redis->get('utoken:' . $uid);
        if ($utoken_in_redis == $utoken) {
            $this->isLogin = true;
        } else {
            $this->isLogin = false;
        }
    }
}