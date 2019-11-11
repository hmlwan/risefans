<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class TaskController extends HomeController
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

    public function index()
    {
        $member_id = session('USER_KEY_ID');
        $mem_info = D("Member")->get_info_by_id($member_id);
        $mem_info["phone_hide"] = substr_replace($mem_info['phone'],'****',3,4);
        /*每日签到*/
        $sign_conf_db = M('sign_conf');
        $sign_conf = $sign_conf_db->where("1=1")->find();

        /*红包配置*/
        $redpack_conf = M('hongbao_conf')->where("1=1")->find();

        /*邀请配置*/
        $invite_conf = M("invite_conf")->where("1=1")->find();

        /*购买贡献值配置*/
        $contribution_conf = M("contribution_conf")->where("1=1")->find();

        /*每日签到记录*/
        $daily_luckdraw_db = M('daily_luckdraw');
        $daily_record =  $daily_luckdraw_db->where(array('member_id'=>$member_id))->select();

        $this->display();
    }

}