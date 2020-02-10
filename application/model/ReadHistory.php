<?php


namespace app\model;


use think\Model;

class ReadHistory extends Model
{
    protected $pk = 'id';
    public $autoWriteTimestamp = 'datetime';
    protected $json = ['book_id'];
//    protected $jsonType = [
//        ''
//    ];
    protected $jsonAssoc = true;
//
//    public function getBookIdAttr($book_id_origin)
//    {
//        return unserialize($book_id_origin);
//    }
//
//    public function setBookIdAttr($book_id)
//    {
//        return serialize($book_id);
//    }

//    public function book()
//    {
//        return $this->hasOne('Book');
//    }
//
//    public function user()
//    {
//        return $this->hasOne('User');
//    }

}