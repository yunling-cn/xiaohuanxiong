<?php
namespace app\admin\validate;


use think\Validate;

class Banner extends Validate
{

    protected $rule = [
        'title' => 'require',
        'book_id' => 'require|number',
        'banner_order' => 'require|number'
    ];

    protected $message = [
        'title' => '标题必须',
        'book_id' => '漫画id必须是数字',
        'banner_order' => '漫画排序必须是数字'
    ];
}