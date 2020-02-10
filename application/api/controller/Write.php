<?php
/**
 * Created by PhpStorm.
 * User: hiliq
 * Date: 2019/3/1
 * Time: 21:19
 */

namespace app\api\controller;

use app\model\Author;
use app\model\Book;
use app\model\Photo;
use Overtrue\Pinyin\Pinyin;
use think\Controller;
use think\Db;
use think\Exception;
use think\Request;
use app\model\Chapter;

class Write extends Controller
{
    protected $chapterService;
    protected $photoService;
    protected $conn;
    protected $db;

    public function initialize()
    {
        $this->chapterService = new \app\service\ChapterService();
        $this->photoService = new \app\service\PhotoService();
        $this->conn = [
            'type' => 'mysql',
            'hostname' => '数据库ip',
            'database' => '数据库名',
            'username' => '数据库名',
            'password' => '数据库密码',
            'hostport' => '端口号',
            'charset' => 'utf8',
            'enable' => false,
        ];
    }

    protected function db()
    {
        if ($this->conn['enable']) {
            unset($this->conn['enable']);
            return Db::connect($this->conn);
        } else {
            return Db::connect();
        }
    }

    public function save(Request $request)
    {
        if ($request->isPost()) {
            $data = $request->param();

            if (!array_key_exists('api_key', $data)) {//修复PHP7 数组索引不存在
                return 'api密钥不能为空！';
            }
            $key = $data['api_key'];
            if ($key != config('site.api_key')) {
                return 'api密钥错误！';
            }

            $where[] = ['src_url', '=', $data['src_url']];
            $where[] = ['src', '=', $data['src']];
            $booklog = $this->db()->name('booklogs')->where($where)->find();
            if (empty($booklog)) { //如果漫画不存在
                $author = Author::where('author_name', '=', trim($data['author']))->find();
                if (is_null($author)) {//如果作者不存在
                    $author = new Author();
                    $author->author_name = $data['author'] ?: '侠名';
                    $author->save();
                }
                $book = new Book();
                $book->author_id = $author->id;
                $book->author_name = $data['author'] ?: '侠名';
                $book->area_id = trim($data['area_id']);
                $book->book_name = trim($data['book_name']);
                if (!empty($data['nick_name']) || !is_null($data['nick_name'])) {
                    $book->nick_name = trim($data['nick_name']);
                }
                $book->tags = trim($data['tags']);
                $book->end = trim($data['end']);
                $book->cover_url = trim($data['cover_url']);
                $book->summary = trim($data['summary']);
                $book->last_time = array_key_exists('last_time', $data) ? $data['last_time'] : time();
//                $str = $this->convert($book->book_name); //生成标识

                $book->unique_id = $this->convert($book->book_name . $data['src_url'] . microtime(true)); //生成标识

//                if (Book::where('unique_id','=',$str)->select()->count() > 0) { //如果已经存在相同标识
//                    $book->unique_id = md5(microtime(true));
//                    //这个暂停是干嘛
////                    sleep(0.1);
//                } else {
//                    $book->unique_id = $str;
//                }
                $book->save();
                $book_id = $book->id;

                $result = $this->db()->name('booklogs')->insert([
                    'book_id' => $book_id,
                    'src_url' => $data['src_url'],
                    'src' => $data['src']
                ]);
            } else {
                $book_id = $booklog['book_id'];
                $book = Book::get($book_id);
                $book->update_time = time();
                $result = $book->save();
            }
            $result2 = $this->addChapter($book_id, $data);
            //添加反馈
            if ($result && $result2) {
                return 'success';
            } else {
                return 'failed';
            }
        }
    }

    public function addChapter($book_id, $data)
    {
        $chapterlog = $this->db()->name('chapterlogs')->where('c_src_url', '=', $data["c_src_url"])->find();
        if (empty($chapterlog)) {
            $chapter = new Chapter();
            $chapter->chapter_name = trim($data['chapter_name']);
            $chapter->book_id = $book_id;
            $lastChapterOrder = 0;
            $lastChapter = $this->chapterService->getLastChapter($book_id);
            if ($lastChapter) {
                $lastChapterOrder = $lastChapter->chapter_order;
            }
            //火车头可控章节顺序
            $chapter->chapter_order = array_key_exists('chapter_order', $data) ? $data['chapter_order'] : $lastChapterOrder + 1;
            $chapter->save();

            $this->db()->name('chapterlogs')->insert([
                'book_id' => $book_id,
                'chapter_id' => $chapter->id,
                'c_src_url' => $data["c_src_url"]
            ]);
            $preg = '/\bsrc\b\s*=\s*[\'\"]?([^\'\"]*)[\'\"]?/i';
            preg_match_all($preg, $data['images'], $img_urls);
            $lastOrder = 0;
            $lastPhoto = $this->photoService->getLastPhoto($chapter->id);
            if ($lastPhoto) {
                $lastOrder = array_key_exists('chapter_order', $data) ? $data['chapter_order'] : $lastPhoto->pic_order + 1;
            }
            //判断是否都插入成功
            $determine = true;
            foreach ($img_urls[1] as $img_url) {
                $photo = new Photo();
                $photo->chapter_id = $chapter->id;
                $photo->pic_order = $lastOrder;
                $photo->img_url = $img_url;
                $determine = $photo->save() && $determine;
                $lastOrder++;
            }
            return $determine;
        }
    }

    protected function convert($str){
        $pinyin = new Pinyin();
        $name_format = config('seo.name_format');
        switch ($name_format) {
            case 'pure':
                $arr = $pinyin->convert($str);
                $str = implode($arr,'');
                halt($str);
                break;
            case 'abbr':
                $str = $pinyin->abbr($str);break;
            default:
                $str = $pinyin->convert($str);break;
        }
        return $str;
    }
}