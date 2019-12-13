<?php


namespace app\app\controller;


use app\model\Banner;
use app\model\Book;
use app\model\Chapter;
use app\model\Tags as Tag;
use think\Request;

class Tags extends Base
{
    public function getList()
    {
        $tags = cache('tags');
        if (!$tags) {
            $tags = Tag::all();
            cache('tags', $tags, null, 'redis');
        }

        $result = [
            'success' => 1,
            'tags' => $tags
        ];
        return json($result);
    }

    public function getAreaList()
    {
        $areas = cache('areas');
        if (!$areas) {
            $areas = \app\model\Area::all();
            cache('areas', $areas, null, 'redis');
        }
        return json(['success' => 1, 'areas' => $areas]);
    }

    public function getBookList(Request $request)
    {
        $startItem = input('startItem');
        $pageSize = input('pageSize');

        $map = array();
        $area = $request->param('area');
        if (is_null($area) || $area == '-1') {

        } else {
            $map[] = ['area_id', '=', $area];
        }

        $tag = $request->param('tag');
        if (is_null($tag) || $tag == '全部') {

        } else {
            $map[] = ['tags', 'like', '%' . $tag . '%'];
        }

        $end = $request->param('end');
        if (is_null($end) || $end == -1) {

        } else {
            $map[] = ['end', '=', $end];
        }

        $books = Book::where($map)->order('update_time', 'desc')->limit($startItem, $pageSize)->select();
        foreach ($books as &$book) {
            $book['chapter_count'] = Chapter::where('book_id','=', $book['id'])->count();
            if (empty($book['cover_url'])) {
                $book['cover_url'] = $this->imgUrl . '/static/upload/book/' . $book['id'] . '/cover.jpg';
            }
        }

        return json([
            'success' => 1,
            'books' => $books,
            'count' => count($books)
        ]);
    }

    public function getBanners()
    {
        $num = input('num');
        $banners = cache('bannersHomepage');
        if (!$banners) {
            $banners = Banner::where('banner_order', '>', 0)->order('banner_order', 'desc')->select();
            cache('bannersHomepage', $banners, null, 'redis');
        }
        foreach ($banners as &$banner) {
            $banner['pic_name'] = $this->url . '/static/upload/banner/' . $banner['pic_name'];
        }
        return json(['success' => 1, 'banners' => $banners]);
    }

    public function getBooksByTag(){
        $str = input('str');
        $tags = explode(',',$str);
        $catelist = array(); //分类漫画数组
        $cateItem = array();
        foreach ($tags as $tag) {
            $books = cache('booksFilterByTag:'.$tag);
            if (!$books) {
                $books = Book::where('tags', 'like', '%' . $tag . '%')
                    ->order('id', 'desc')->limit(10)->select();
                foreach ($books as &$book) {
                    if (empty($book['cover_url'])) {
                        $book['cover_url'] = $this->imgUrl.'/static/upload/book/'.$book['id'].'/cover.jpg';
                        $book['chapter_count'] = Chapter::where('book_id','=',$book['id'])->count();
                    }
                }
                cache('booksFilterByTag:'.$tag, $books, null, 'redis');
            }

            $cateItem['books'] = $books->toArray();
            $cateItem['tag'] = $tag;
            $catelist[] = $cateItem;
        }
        return json(['success' => 1, 'cates' => $catelist]);
    }
}