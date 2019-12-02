<?php


namespace app\app\controller;


use app\service\PromotionService;
use think\Request;
use app\model\User;

class Account extends Base
{
    public function register(Request $request)
    {
        $ip = $request->ip();
        $redis = new_redis();
        if ($redis->exists('user_reg:' . $ip)) {
            return json(['success' => 0, 'msg' => '操作太频繁']);
        } else {
            $redis->set('user_reg:' . $ip, 1, 60); //写入锁
            $data = $request->param();
            $validate = new \app\ucenter\validate\User();
            if ($validate->check($data)) {
                $user = \app\model\User::where('username', '=', trim($request->param('username')))->find();
                if (!is_null($user)) {
                    return json(['success' => 0, 'msg' => '用户名已经存在']);
                }
                $user = new Account();
                $user->username = trim($request->param('username'));
                $user->password = trim($request->param('password'));
                $pid = $request->param('pid');
                if (!$pid || $pid == null) {
                    $pid = 0;
                }
                $user->pid = $pid; //设置用户上线id
                $result = $user->save();
                if ($result) {
                    $promotionService = new PromotionService();
                    $promotionService->rewards($user->id, (float)config('payment.reg_rewards'), 2); //调用推广处理函数
                    return json(['success' => 1, 'msg' => '注册成功，请登录']);
                } else {
                    return json(['success' => 0, 'msg' => '注册失败，请尝试重新注册']);
                }
            } else {
                return json(['success' => 0, 'msg' => $validate->getError()]);
            }
        }
    }

    public function login(Request $request)
    {
        $map = array();
        $map[] = ['username', '=', trim($request->param('username'))];
        $map[] = ['password', '=', md5(strtolower(trim($request->param('password'))) . config('site.salt'))];
        $user = User::withTrashed()->where($map)->find();
        if (is_null($user)) {
            return json(['success' => 0, 'msg' => '用户名或密码错误']);
        } else {
            if ($user->delete_time > 0) {
                return json(['success' => 0, 'msg' => '用户被锁定']);
            } else {
                $utoken = md5($user->username . config('api_key'));
                $redis = new_redis();
                $redis->set('utoken:' . $user->id, $utoken, 3600 * 24);
                $userInfo = [];
                $userInfo['uid'] = $user->id;
                $userInfo['username'] = $user->username;
                $userInfo['nick_name'] = $user->nick_name;
                $userInfo['mobile'] = $user->mobile;
                $userInfo['vip_expire_time'] = $user->vip_expire_time;
                $userInfo['utoken'] = $utoken;

                return json(['success' => 1, 'userInfo' => $userInfo]);
            }
        }
    }

    public function logout()
    {
        $uid = input('uid');
        $redis = new_redis();
        $redis->del('utoken:' . $uid);
        return ['success' => 1, 'msg' => '登出成功'];
    }
}