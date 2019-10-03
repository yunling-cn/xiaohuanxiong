<?php


namespace app\admin\validate;


use think\Validate;

class Page extends Validate
{
    protected $rule = [
        'back_end_page' => 'require|integer',
        'booklist_pc_page' => 'require|integer',
        'booklist_mobile_page' => 'require|integer',
        'update_pc_page' => 'require|integer',
        'update_mobile_page' => 'require|integer',
        'search_result_mobile' => 'require|integer',
        'search_result_pc' => 'require|integer'
    ];


    protected $message = [
        'back_end_page' => '选项必须是整数',
        'booklist_pc_page' => '选项必须是整数',
        'booklist_mobile_page' => '选项必须是整数',
        'update_pc_page' => '选项必须是整数',
        'update_mobile_page' => '选项必须是整数',
        'search_result_mobile' => '选项必须是整数',
        'search_result_pc' => '选项必须是整数'
    ];
}