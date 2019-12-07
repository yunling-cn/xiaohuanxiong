<?php


namespace app\app\controller;


use app\model\Chapter;
use app\model\UserBuy;
use Firebase\JWT\JWT;
use think\Db;

class Chapters extends Base
{
    public function getList()
    {
        $book_id = input('book_id');

        $chapters = cache('chapters:'.$book_id);
        if (!$chapters) {
            $chapters = Chapter::where('book_id', '=', $book_id)->select();
            cache('chapters:'.$book_id, $chapters, null, 'redis');
        }

//        foreach ($chapters as &$chapter) {
//            $imgs = Photo::where('chapter_id','=',$chapter->id)->select();
//            if (empty($imgs[0]->img_url)) {
//                $chapter['cover'] = $this->imgUrl.'/static/upload/book/'.$book_id.'/'.$chapter->id.'/'.$imgs[0]->id.'.jpg';
//            } else {
//                $chapter['cover'] = $imgs[0]->img_url;
//            }
//        }

        $result = [
            'success' => 1,
            'chapters' => $chapters
        ];
        return json($result);
    }

    public function detail()
    {
        $id = input('id');
        $utoken = input('utoken');
        $key = config('site.api_key');
        if (isset($utoken)) {
            $data = $this->checkAuth();
            if ($data['success'] == 1) {
                $this->uid = $data['uid'];
            }
        }
        $chapter = Chapter::with(['photos' => function ($query) {
            $query->order('pic_order');
        }], 'book')->cache('chapter:' . $id, 600, 'redis')->find($id);
        $flag = true;
        if ($chapter->chapter_order >= $chapter->book->start_pay) { //如果本章是本漫画设定的付费章节
            $flag = false;
        }
        if (isset($this->uid)) { //如果用户已经登录
            $vip_expire_time = session('xwx_vip_expire_time'); //用户等级
            $time = $vip_expire_time - time(); //计算vip用户时长
            if ($time > 0) { //如果是vip会员且没过期，则可以不受限制
                $flag = true;
            } else { //如果不是会员，则判断用户是否购买本章节
                $map[] = ['user_id', '=', $this->uid];
                $map[] = ['chapter_id', '=', $id];
                $userBuy = UserBuy::where($map)->find();
                if (!is_null($userBuy) || !empty($userBuy)) { //说明用户购买了本章节
                    $flag = true;
                }
            }
        }

        if ($flag) {
            $book_id = $chapter->book_id;
            $prev = cache('chapter_prev:' . $id);
            if (!$prev) {
                $prev = Db::query(
                    'select * from ' . $this->prefix . 'chapter where book_id=' . $book_id . ' and chapter_order<' . $chapter->chapter_order . ' order by id desc limit 1');
                cache('chapter_prev:' . $id, $prev, null, 'redis');
            }
            $next = cache('chapter_next:' . $id);
            if (!$next) {
                $next = Db::query(
                    'select * from ' . $this->prefix . 'chapter where book_id=' . $book_id . ' and chapter_order>' . $chapter->chapter_order . ' order by id limit 1');
                cache('chapter_next:' . $id, $next, null, 'redis');
            }

            $chapter['prev'] = count($prev) > 0 ? $prev[0]['id'] : null;
            $chapter['next'] = count($next) > 0 ? $next[0]['id'] : null;

            $result = [
                'success' => 1,
                'chapter' => $chapter
            ];

        } else {
            $result = [
                'success' => 0,
                'msg' => '没有购买此章节'
            ];
        }
        return json($result);
    }
}