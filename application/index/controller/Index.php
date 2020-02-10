<?php

namespace app\index\controller;

use app\model\Author;
use app\model\Banner;
use app\model\HotSearch;
use app\model\RedisHelper;
use app\model\Tags as Tag;
use think\App;

class Index extends Base
{
    protected $bookService;

    public function __construct(App $app = null)
    {
        if (!file_exists(__DIR__ . DS . '..' . DS . '..' . DS . 'install' . DS . 'install.lock')) {
            header("Location: /install");
            exit;
        }
        parent::__construct($app);

    }

    protected function initialize()
    {
        parent::initialize();
        $this->bookService = new \app\service\BookService();
    }

    public function index()
    {
        $pid = input('pid');
        if ($pid) { //如果有推广pid
            cookie('xwx_promotion', $pid); //将pid写入cookie
        }
        $banners = cache('bannersHomepage');
        if (!$banners) {
            $banners = Banner::with('book')->where('banner_order','>', 0)->order('banner_order','desc')->select();
            cache('bannersHomepage',$banners, null, 'redis');
        }
        $hot_books = cache('hotBooks');
        if (!$hot_books) {
            $hot_books = $this->bookService->getHotBooks('1900-01-01', 9);
            cache('hotBooks', $hot_books, null, 'redis');
        }
        trace($hot_books);

        $newest = cache('newestHomepage');
        if (!$newest) {
            $newest = $this->bookService->getBooks('last_time', '1=1', 9);
            cache('newestHomepage', $newest, null, 'redis');
        }
        trace($newest->toArray());

        $ends = cache('endsHomepage');
        if (!$ends) {
            $ends = $this->bookService->getBooks('create_time', [['end', '=', '1']], 9);
            cache('endsHomepage', $ends, null, 'redis');
        }

        $most_charged = cache('mostCharged');
        if (!$most_charged) {
            $arr = $this->bookService->getMostChargedBook();
            if (count($arr) > 0) {
                foreach ($arr as $item) {
                    $most_charged[] = $item['book'];
                }
            } else {
                $arr = [];
            }
            cache('mostCharged', $most_charged, null, 'redis');
        }

        $tags = cache('tags');
        if (!$tags) {
            $tags = \app\model\Tags::all();
            cache('tags', $tags, null, 'redis');
        }

        $catelist = array(); //分类漫画数组
        $cateItem = array();
        foreach ($tags as $tag) {
            $books = cache('booksFilterByTag:' . $tag);
            if (!$books) {
                $books = $this->bookService->getByTag($tag->tag_name);
                cache('booksFilterByTag:' . $tag, $books, null, 'redis');
            }
            $cateItem['books'] = $books->toArray();
            $cateItem['tag'] = ['id' => $tag->id, 'tag_name' => $tag->tag_name];
            $catelist[] = $cateItem;
        }

//        trace($hot_books);

        $this->assign([
            'banners' => $banners,
            'banners_count' => count($banners),
            'newest' => $newest,
            'hot' => $hot_books,
            'ends' => $ends,
            'most_charged' => $most_charged,
            'tags' => $tags,
            'catelist' => $catelist,
        ]);

        return view($this->tpl);
    }
 
    public function search()
    {
        $keyword = input('keyword');

        //弃用Redis
//        $redis = RedisHelper::GetInstance();
//        $redis->zIncrBy($this->redis_prefix . 'hot_search', 1, $keyword); //搜索词写入redis热搜
//        $hot_search_json = $redis->zRevRange($this->redis_prefix . 'hot_search', 0, 4, true);
//        $hot_search = array();
//        foreach ($hot_search_json as $k => $v) {
//            $hot_search[] = $k;
//        }

        //添加热搜词
        if ($keyword) {
            $hot_search_model = HotSearch::get(['keyword' => $keyword]);
            if ($hot_search_model) {
                $hot_search_model->searches = ['inc', 1];
                $hot_search_model->save();
            } else {
                $hot_search_model = new HotSearch();
                $hot_search_model->save([
                    'keyword' => $keyword,
                    'searches' => 1
                ]);
            }
        }
        //获取所有热搜词
        $hot_search = HotSearch::order('searches', 'desc')->column('keyword');
        trace($hot_search);

        //搜索书
        if ($keyword) {
//            trace($keyword);
            $books = cache('searchresult:' . $keyword);
//            trace(148);
            if (!$books) {
//                trace(var_export($books,true));
                $num = config('page.search_result_pc');
                if ($this->request->isMobile()) {
                    $num = config('page.search_result_mobile');
                }
                $books = $this->bookService->search($keyword, $num);
//                trace(var_export($books,true));
                cache('searchresult:' . $keyword, $books, null, 'redis');
            }
            foreach ($books as &$book) {
                $author = Author::get($book['author_id']);
                $book['author'] = $author;
                if (empty($book['cover_url'])) {
                    $book['cover_url'] = $this->img_site . '/static/upload/book/' . $book['id'] . '/cover.jpg';
                }
                if ($this->end_point == 'id') {
                    $book['param'] = $book['id'];
                } else {
                    $book['param'] = $book['unique_id'];
                }
            }
//            trace(var_export($books,true));
        }

        //获取分类
        $tags = cache('tags');
        if (!$tags) {
            $tags = Tag::all();
            cache('tags', $tags, null, 'redis');
        }

//        !empty($books)?trace($books):false;
//        trace($keyword);

        //TODO: 分页没写完
        $this->assign([
            'books' => empty($books) ? [] : $books,
            'tags' => $tags,
            'count' => empty($books) ? 0 : count($books),
            'hot_search' => $hot_search,
            'keyword' => $keyword
        ]);
        return view($this->tpl);
    }

    public function bookshelf()
    {
        $this->assign('header_title', '书架');
        return view($this->tpl);
    }
}

