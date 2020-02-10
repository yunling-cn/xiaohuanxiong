<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/3/9
 * Time: 11:47
 */

namespace app\index\controller;


use app\model\Book;
use think\Db;

class Rank extends Base
{
    protected $bookService;

    protected function initialize()
    {
        parent::initialize();
        $this->bookService = new \app\service\BookService();
    }

    public function index()
    {
        $page = input('?page') ? input('page') : 1;
        if ($this->request->isMobile()) {
            $pagenum = config('page.rank_result_mobile');
        } else {
            $pagenum = config('page.rank_result_pc');
        }

        $hot_books = cache('hot_books');
        if (!$hot_books) {
            $hot_books = $this->bookService->getHotBooks('1900-01-01', $pagenum * $page);
            cache('hot_books', $hot_books, null, 'redis');
        }
        $hot_books = array_slice($hot_books, ($page - 1) * $pagenum, $pagenum);

        $newest = cache('newest_homepage|' . $page . '|' . $pagenum);
        if (!$newest) {
            $newest = $this->bookService->getBooks('last_time', '1=1', $pagenum, $page);
            cache('newest_homepage' . $page . '|' . $pagenum, $newest, null, 'redis');
        }
        $newest = $newest->toArray();

        $ends = cache('ends_homepage' . $page . '|' . $pagenum);
        if (!$ends) {
            $ends = $this->bookService->getBooks('create_time', [['end', '=', '1']], $pagenum, $page);
            cache('ends_homepage' . $page . '|' . $pagenum, $ends, null, 'redis');
        }
        $ends = $ends->toArray();


        $most_charged = cache('most_charged');
        if (!$most_charged) {
            $arr = $this->bookService->getMostChargedBook();
            if (count($arr) > 0) {
                foreach ($arr as $item) {
                    $most_charged[] = $item['book'];
                }
            } else {
                $arr = [];
            }
            cache('most_charged', $most_charged, null, 'redis');
        }
        if (empty($most_charged)) {
            $most_charged = [];
        }
        $most_charged = array_slice($most_charged, ($page - 1) * $pagenum, $pagenum);

        $bookCount = Db::name('book')->cache(true)->count();


        trace(ceil($bookCount / $pagenum));
        $this->assign([
            'list' => [
                ['keyword' => $newest, 'title' => '新书榜', 'id' => 'newest', 'pages' => ceil($bookCount / $pagenum)],
                ['keyword' => $hot_books, 'title' => '人气榜', 'id' => 'hot', 'pages' => ceil($bookCount / $pagenum)],
                ['keyword' => $ends, 'title' => '完结榜', 'id' => 'ends', 'pages' => ceil($bookCount / $pagenum)],
                ['keyword' => $most_charged, 'title' => '充值榜', 'id' => 'charge', 'pages' => ceil(count($most_charged) / $pagenum)]
            ],
            'header_title' => '排行榜',
            'page' => $page,
        ]);

        return view($this->tpl);
    }
}