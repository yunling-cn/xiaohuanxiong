<?php


namespace app\model;


use think\Model;

class Message extends Model
{
    protected $pk = 'id';

    public $autoWriteTimestamp = 'datetime';

}