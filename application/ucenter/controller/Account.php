<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/2/25
 * Time: 15:55
 */

namespace app\ucenter\controller;


use app\model\RedisHelper;
use app\model\User;
use app\service\PromotionService;
use think\App;
use think\captcha\Captcha;
use think\Controller;
use think\facade\Env;
use think\facade\View;
use think\Request;

class Account extends Controller
{
    protected $tpl;

    public function __construct(App $app = null)
    {

        parent::__construct($app);

//        exit();
        $controller = strtolower($this->request->controller());
        $action = strtolower($this->request->action());
        if ($this->request->isMobile()) {
            $tpl_root = Env::get('root_path') . '/public/template/' . config('site.touch_front_tpl') . '/ucenter/';
            $this->tpl = $tpl_root . $controller . '/' . $action . '.html';
        } else {
            $tpl_root = Env::get('root_path') . '/public/template/' . config('site.pc_front_tpl') . '/ucenter/';
            $this->tpl = $tpl_root . $controller . '/' . 'pc_' . $action . '.html';
        }
        $mobile_bind_rewards = config('payment.mobile_bind_rewards');
        \think\facade\View::share([
            'mobile_bind_rewards' => '登录后请立刻绑定手机号，确保账户密码遗忘，还会赠送' . $mobile_bind_rewards . '元书币',
            'custom_url' => \think\facade\Request::url(true),
        ]);
//        trace(config('root_path'));
    }

    public function register(Request $request)
    {
        if ($request->isPost()) {
            $captcha = $request->param('captcha');
            $captchar = new Captcha();
            if (!$captchar->check($captcha)) {
                return json(['err' => 1, 'msg' => '验证码错误', 'status' => 0]);
            }
            $ip = $request->ip();
//            $redis = RedisHelper::GetInstance();
            if (cache('user_reg:' . $ip)) {
                return json(['err' => 1, 'msg' => '操作太频繁', 'status' => 0]);
            } else {
                $data = $request->param();
                $validate = new \app\ucenter\validate\User();
                if ($validate->check($data)) {
                    $user = User::where('username', '=', trim($request->param('username')))->find();
                    if (!is_null($user)) {
                        return json(['err' => 1, 'msg' => '用户名已经存在', 'status' => 0]);
                    }
                    $user = new User();
                    $user->username = trim($request->param('username'));
                    $user->password = trim($request->param('password'));
                    $pid = cookie('xwx_promotion');
                    if (!$pid) {
                        $pid = 0;
                    }
                    $user->pid = $pid; //设置用户上线id
                    $user->reg_ip = $request->ip();
                    $result = $user->save();
                    if ($result) {
//                        $redis->set('user_reg:'.$ip,1,60); //写入锁
                        cache('user_reg:' . $ip, 1, 60);
                        if ($pid > 0) {
                            $puser = User::find($pid);
                            if ($puser) {
                                if ($puser->vip_expire_time < time()) { //说明vip已经过期
                                    $puser->vip_expire_time = time() + 24 * 60 * 60;
                                } else { //vip没过期，则在现有vip时间上增加
                                    $puser->vip_expire_time = $user->vip_expire_time + 24 * 60 * 60;
                                }
                            }
                        }


                        return json(['err' => 0, 'msg' => '注册成功，请登录', 'success' => 1, 'status' => 0]);
                    } else {
                        return json(['err' => 1, 'msg' => '注册失败，请尝试重新注册', 'status' => 0]);
                    }
                } else {
                    return json(['err' => 1, 'msg' => $validate->getError(), 'status' => 0]);
                }

            }

        } else {
            $this->assign([
                'site_name' => config('site.site_name'),
                'url' => config('site.url'),
                'header_title' => '注册',
                'book_ctrl' => BOOKCTRL,
                'chapter_ctrl' => CHAPTERCTRL,
                'tag_ctrl' => TAGCTRL,
                'booklist_act' => BOOKLISTACT,
                'search_ctrl' => SEARCHCTRL,
                'rank_ctrl' => RANKCTRL,
                'update_act' => UPDATEACT,
                'author_ctrl' => AUTHORCTRL
            ]);
            return view($this->tpl);
        }
    }

    public function login(Request $request)
    {
        if ($request->isPost()) {
            $captcha = $request->param('captcha');
            //助手函数有点问题，不支持MIP的异步提交
            $captchar = new Captcha();
            if (!$captchar->check($captcha)) {
                return json(['status' => 0, 'err' => 1, 'msg' => '验证码错误']);//兼容MIP
            }
            $map = array();
            $map[] = ['username', '=', trim($request->param('username'))];
            $map[] = ['password', '=', md5(strtolower(trim($request->param('password'))) . config('site.salt'))];
            $user = User::withTrashed()->where($map)->find();

            if (is_null($user)) {
                return json(['status' => 0, 'err' => 1, 'msg' => '用户名或密码错误']);
            } else {
                if ($user->delete_time > 0) {
                    return json(['status' => 0, 'err' => 1, 'msg' => '用户被锁定']);
                } else {
                    $user->last_login_time = time();
                    $user->isUpdate(true)->save();
                    session('xwx_user', $user->username);
                    session('xwx_user_id', $user->id);
                    session('xwx_nick_name', $user->nick_name);
                    session('xwx_user_mobile', $user->mobile);
                    session('xwx_vip_expire_time', $user->vip_expire_time);
                    return json(['status' => 0, 'err' => 0, 'msg' => '登录成功', 'success' => 1]);
                }

            }
        } else {
            $this->assign([
                'site_name' => config('site.site_name'),
                'url' => config('site.url'),
                'header_title' => '登录',
                'book_ctrl' => BOOKCTRL,
                'chapter_ctrl' => CHAPTERCTRL,
                'tag_ctrl' => TAGCTRL,
                'booklist_act' => BOOKLISTACT,
                'search_ctrl' => SEARCHCTRL,
                'rank_ctrl' => RANKCTRL,
                'update_act' => UPDATEACT,
                'author_ctrl' => AUTHORCTRL
            ]);
//            trace($this->tpl);
            return view($this->tpl);
        }
    }

    public function logout()
    {
        session('xwx_user', null);
        session('xwx_user_id', null);
        session('xwx_nick_name', null);
        session('xwx_user_mobile', null);
        session('xwx_vip_expire_time', null);
        if ($this->request->isMobile()) {
            $this->redirect('/login');
        }
        $this->success('成功登出', '/login');
    }

    public function getCaptcha($captcha_code)
    {
        $captcha = new Captcha();

        if ($captcha->check($captcha_code)) {
            cache('recovery:' . session('xwx_user_id'), 1, 300);
            return json(['status' => 0, 'msg' => '验证成功', 'success' => 1]);
        } else {
            return json(['msg' => '验证失败，请取消后再试']);
        }
    }

    public function recovery()
    {
        if ($this->request->isPost()) {
            if ($this->request->isMobile()) {
                if (!cache('recovery:' . session('xwx_user_id'))) {
                    return json(['err' => 1, 'msg' => '请先输入图形验证码']);
                }
                if (input('?password') && input('?password2') && input('password') == input('password2')) {
                    return json(['err' => 1, 'msg' => '两次输入密码不一样']);
                }
            }
            //TODO:找回密码未完成
            $code = trim(input('code'));
            $phone = trim(input('emailorphone'));
            if (verifycode($code, $phone) == 0) {
                return json(['err' => 1, 'msg' => '验证码不正确', 'status' => 0]);
            }
            $pwd = input('txt_password');
            $user = User::where('mobile', '=', $phone)->find();
            if (is_null($user)) {
                return json(['err' => 1, 'msg' => '该手机号不存在', 'status' => 0]);
            }
            $user->password = $pwd;
            $user->isUpdate(true)->save();
            return json(['err' => 0, 'msg' => '修改成功', 'status' => 0]);
        }
        $phone = input('phone');
        $this->assign([
            'phone' => $phone,
            'header_title' => '找回密码',
            'site_name' => config('site.site_name'),
            'url' => config('site.url'),
            'book_ctrl' => BOOKCTRL,
            'chapter_ctrl' => CHAPTERCTRL,
            'tag_ctrl' => TAGCTRL,
            'booklist_act' => BOOKLISTACT,
            'search_ctrl' => SEARCHCTRL,
            'rank_ctrl' => RANKCTRL,
            'update_act' => UPDATEACT,
            'author_ctrl' => AUTHORCTRL
        ]);
        return view($this->tpl);
    }
}