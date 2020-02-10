<?php


namespace app\service;


use app\model\ReadHistory;

class ReadHistoryService
{
    protected $bookService;

    public function getBooksByUid($uid, $current_page = false, $page_num = false)
    {
        $history = $this->newHistory($uid);
//        $history -> uid = $uid;
//        $history -> book_id = [1,2,3,4,5,6];
//        $history -> save();
//        $history = ReadHistory::get(['uid' => $uid]);
//        trace($history);
//        trace($history -> toArray());
        $this->bookService = new BookService();
        $books = $history->book_id;

        foreach ($books as $k => $id) {
            //TODO:应该改用一对一关联
            $book = $this->bookService->getBooksById($id);
            $books[$k] = is_object($book) ? $book->toArray()[0] : $book;
        }
        $total = count($books);
        //分页
        if ($current_page !== false && $page_num !== false) {
            $books_ = $books;
            $books = [];
            $books['data'] = array_slice($books_, ($current_page - 1) * $page_num, $page_num);
            $books['current_page'] = $current_page;
            $books['last_page'] = ceil($total / $page_num);
            $books['total'] = $total;
            $books['page_num'] = $page_num;
        }
//        cache('history:'.$uid,json_encode($books));
        return $books;
    }

    public function addBook($uid, $book_id)
    {
        $history = $this->newHistory($uid);
        $books = $history->book_id;
        if (!in_array($book_id, $books)) {
            $books[] = $book_id;
        }

        $books = json_encode($books);
//        cache('history:'.$uid,$books);
        $r = $history->save(['book_id' => $books]);
        return $r;
    }

    protected function newHistory($uid)
    {
        $history = ReadHistory::get(['uid' => $uid]);
        if (empty($history)) {
            $history = new ReadHistory();
            $history->save(['uid' => $uid, 'book_id' => []]);
            $history = ReadHistory::get(['uid' => $uid]);
        }
        return $history;
    }
}