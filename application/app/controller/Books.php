<?php


namespace app\app\controller;


use app\model\Area;
use app\model\Author;
use app\model\Book;
use app\model\Chapter;
use app\model\Comments;
use think\Db;

class Books extends Base
{
    protected $bookService;


    public function initialize()
    {
        parent::initialize();
        $this->bookService = new \app\service\BookService();
    }

    public function getNewest()
    {
        $newest = cache('newest_homepage');
        if (!$newest) {
            $newest = Book::limit(10)->order('last_time', 'desc')->select();
            foreach ($newest as &$book) {
                $book['chapter_count'] = Chapter::where('book_id','=', $book['id'])->count();
                if (empty($book['cover_url'])) {
                    $book['cover_url'] = $this->imgUrl . '/static/upload/book/' . $book['id'] . '/cover.jpg';
                }
            }
            cache('newest_homepage', $newest, null, 'redis');
        }
        $result = [
            'success' => 1,
            'newest' => $newest
        ];
        return json($result);
    }

    public function getHot()
    {
        $hot_books = cache('hot_books');
        if (!$hot_books) {
            $hot_books =$this->bookService->getHotBooks();
            foreach ($hot_books as &$book) {
                if (empty($book['cover_url'])) {
                    $book['cover_url'] = $this->imgUrl . '/static/upload/book/' . $book['id'] . '/cover.jpg';
                }
            }
            cache('hot_books', $hot_books, null, 'redis');
        }
        $result = [
            'success' => 1,
            'hots' => $hot_books
        ];
        return json($result);
    }

    public function getEnds()
    {
        $ends = cache('ends_homepage');
        if (!$ends) {
            $ends = Book::limit(10)->order('last_time', 'desc')->select();
            foreach ($ends as &$book) {
                $book['chapter_count'] = Chapter::where('book_id','=', $book['id'])->count();
                if (empty($book['cover_url'])) {
                    $book['cover_url'] = $this->imgUrl . '/static/upload/book/' . $book['id'] . '/cover.jpg';
                }
            }
            cache('ends_homepage', $ends, null, 'redis');
        }
        $result = [
            'success' => 1,
            'ends' => $ends
        ];
        return json($result);
    }

//    public function getMostCharged()
//    {
//        $most_charged = cache('most_charged');
//        if (!$most_charged) {
//            $arr = $this->bookService->getMostChargedBook();
//            foreach ($arr as $item) {
//                $most_charged[] = $item['book'];
//            }
//            cache('most_charged', $most_charged, null, 'redis');
//        }
//        $result = [
//            'success' => 1,
//            'most_charged' => $most_charged
//        ];
//        return json($result);
//    }

    public function search()
    {
        $keyword = input('keyword');
        $redis = new_redis();
        $redis->zIncrBy($this->redis_prefix . 'hot_search:', 1, $keyword);
        $hot_search_json = $redis->zRevRange($this->redis_prefix . 'hot_search', 1, 4, true);
        $hot_search = array();
        foreach ($hot_search_json as $k => $v) {
            $hot_search[] = $k;
        }
        $books = cache('appsearchresult:' . $keyword);
        if (!$books) {
            $books = $this->bookService->search($keyword, 20);
            cache('appsearchresult:' . $keyword, $books, null, 'redis');
        }
        foreach ($books as &$book) {
            $author = Author::get($book['author_id']);
            $book['author'] = $author;
            if (empty($book['cover_url'])) {
                $book['cover_url'] = $this->imgUrl . '/static/upload/book/' . $book['id'] . '/cover.jpg';
            }
        }

        $result = [
            'success' => 1,
            'books' => $books,
            'count' => count($books),
            'hot_search' => $hot_search
        ];
        return json($result);
    }

    public function detail()
    {
        $id = input('id');
        $uid = input('uid');
        $book = cache('book:' . $id);
        if ($book == false) {
            $book = Book::with('area')->find($id);
            if (!empty($book)) {
                if (empty($book->cover_url)) {
                    $book->cover_url = $this->imgUrl . '/static/upload/book/' . $id . '/cover.jpg';
                }
            }
            cache('book:' . $id, $book, null, 'redis');
        }

        $redis = new_redis();
        $day = date("Y-m-d", time());
        //以当前日期为键，增加点击数
        $redis->zIncrBy('click:' . $day, 1, $book->id);

        $start = cache('book_start:' . $id);
        if ($start == false) {
            $db = Db::query('SELECT id FROM ' . $this->prefix . 'chapter WHERE book_id = ' . $id . ' ORDER BY id LIMIT 1');
            $start = $db ? $db[0]['id'] : -1;
            cache('book_start:' . $id, $start, null, 'redis');
        }

        $book['start'] = $start;
        $result = [
            'success' => 1,
            'book' => $book
        ];
        return json($result);
    }

    public function getComments()
    {
        $book_id = input('book_id');
        $comments = cache('comments:' . $book_id);
        if (!$comments) {
            $comments = Comments::with('user')->where('book_id', '=', $book_id)
                ->order('create_time', 'desc')->limit(0, 10)->select();
            cache('comments:' . $book_id, $comments);
        }
//        $dir = App::getRootPath() . 'public/static/upload/comments/' . $book_id;
//        foreach ($comments as &$comment) {
//            $comment['content'] = file_get_contents($dir . '/' . $comment->id . '.txt');
//        }
        $result = [
            'success' => 1,
            'comments' => $comments
        ];
        return json($result);
    }

    public function getRecommend(){
        $book_id = input('book_id');
        $book = Book::get($book_id);
        $recommends = cache('randBooks:'.$book->tags);
        if (!$recommends) {
            $recommends = $this->bookService->getRecommand($book->tags);
            cache('randBooks:'.$book->tags, $recommends, null, 'redis');
        }
        foreach ($recommends as &$recommend){
            if (empty($recommend['cover_url'])) {
                $recommend['cover_url'] = $this->imgUrl . '/static/upload/book/' . $recommend['id'] . '/cover.jpg';
            }
            $recommend['area_name'] = Area::get($recommend['area_id'])['area_name'];
        }
        $result = [
            'success' => 1,
            'recommends' => $recommends
        ];
        return json($result);
    }
}