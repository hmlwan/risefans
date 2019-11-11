<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class InviteController extends HomeController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    //空操作
    public function _empty()
    {
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:404');
    }

    /*分享赚钱*/
    public function index()
    {

        $member_id = session('USER_KEY_ID');
        $db = D('Member');

        $this->display();
    }
    /*一级*/
    public function one_level_detail(){
        $this->display();
    }
    /*二级*/
    public function two_level_detail(){
        $this->display();
    }
    /*收益记录*/
    public function income_record(){
        $this->display();

    }
}