<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/2/26
 * Time: 13:48
 */

namespace app\service;

use app\index\controller\Base;
use app\model\User;

class UserService extends Base
{
    public function getFavors($uid)
    {
        $type = 'util\Page';
        if ($this->request->isMobile()) {
            $type = 'util\MPage';
        }
        $user = User::get($uid);
        $books = $user->books()->paginate(10, false,
            [
                'query' => request()->param(),
                'type' => $type,
                'var_page' => 'page',
            ]);
        foreach ($books as &$book) {
            if ($this->end_point == 'id') {
                $book['param'] = $book['id'];
            } else {
                $book['param'] = $book['unique_id'];
            }
        }
        return $books;
    }

    public function delFavors($uid, $ids)
    {
        $user = User::get($uid);
        $user->books()->detach($ids);
    }

//    public function delHistory($uid, $keys)
//    {
//        $redis_prefix = config('cache.prefix');
//        $redis = new_redis();
//        foreach ($keys as $key) {
//            $redis->hDel($redis_prefix . ':history:' . $uid, $key);
//        }
//    }

    public function getAdminPagedUsers($status, $where, $orderBy, $order)
    {
        if ($status == 1) { //正常用户
            $data = User::where($where)->order($orderBy, $order);
        } else {
            $data = User::onlyTrashed()->where($where)->order($orderBy, $order);
        }
        $financeService = new FinanceService();
        $page = config('page.back_end_page');
        $users = $data->paginate($page, false,
            [
                'query' => request()->param(),
                'type' => 'util\AdminPage',
                'var_page' => 'page',
            ])->each(function ($item, $key) use($financeService){
                $item['balance'] = $financeService->getBalance($item->id);
        });
        return [
            'users' => $users,
            'count' => $data->count()
        ];
    }
}