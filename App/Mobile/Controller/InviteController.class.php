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
    public function index(){
        $this->display();

    }

    /*分享赚钱*/
    public function index1()
    {
        $member_id = session('USER_KEY_ID');
        $invite_record_db = D('invite_record');
        /*一级下线*/
        $one_level_num = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>1))
            ->count();
        /*二级下线*/
        $two_level_num = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>2))
            ->count();
        /*已赚金币*/
        $sum_cur_num =  $invite_record_db
            ->where(array('member_id'=>$member_id))
            ->sum('num');
        /*昨日赚金币*/
        $y_start = strtotime(date("Y-m-d 0:0:0",strtotime('-1 day',time())));
        $y_end = strtotime(date("Y-m-d 23:59:59",strtotime('-1 day',time())));
        $yest_where = array(
            'member_id'=>$member_id,
            'add_time'=>array('between',array($y_start,$y_end))
        );
        $yester_sum_cur_num =  $invite_record_db
            ->where($yest_where)
            ->sum('num');

        /*近十条收益数据*/
        $lastly_10 = $invite_record_db->where(array('member_id'=>$member_id,'is_cert'=>2))->order("add_time desc")->limit(10)->select();
        foreach ($lastly_10 as &$value){
            $value['sub_phone'] = M('member')->where(array('member_id'=>$value['sub_member_id']))->getField('phone');
        }
        $this->assign('one_level_num',  $one_level_num );
        $this->assign('two_level_num',  $two_level_num );
        $this->assign('sum_cur_num',  $sum_cur_num );
        $this->assign('lastly_10',  $lastly_10 );
        $this->assign('yester_sum_cur_num',  $yester_sum_cur_num );
        $this->assign('mem_phone',  $_SESSION['USER_KEY'] );

        $this->display();
    }
    /*一级*/
    public function one_level_detail(){

        $data = $this->get_sub_data(1);
        $level_list = $data['level_list'];
        $level_num = $data['level_num'];
        $this->assign('level_list', $level_list );
        $this->assign('level_num', $level_num );
        $this->assign('level_count', count($level_list));
        $this->display();
    }
    /*二级*/
    public function two_level_detail(){
        $data = $this->get_sub_data(2);
        $level_list = $data['level_list'];
        $level_num = $data['level_num'];
        $this->assign('level_list', $level_list );
        $this->assign('level_num', $level_num );
        $this->assign('level_count', count($level_list));
        $this->display();
    }
    /*收益记录*/
    public function income_record(){
        $invite_record_db = D('invite_record');
        $member_id = session('USER_KEY_ID');

        $list = $invite_record_db->where(array('member_id'=>$member_id,'is_cert'=>2))->order("add_time desc")->select();
        foreach ($list as &$value){
            $value['sub_phone'] = M('member')->where(array('member_id'=>$value['sub_member_id']))->getField('phone');
        }
        $this->assign('list', $list );
        $this->display();

    }
    public function get_sub_data($level){
        $member_id = session('USER_KEY_ID');
        $invite_record_db = D('invite_record');
        $db = D('member');
        $level_list = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>$level))
            ->select();
        $level_num = $invite_record_db
            ->where(array('member_id'=>$member_id,'type'=>1,'level'=>$level))
            ->sum('num');
        foreach ($level_list as &$value){
            $info = $db->get_info_by_id($value['sub_member_id']);
            $value['sub_phone'] = $db->where(array('member_id'=>$value['sub_member_id']))->getField('phone');
            $value['head_url'] = $info['head_url'];
        }
        $arr = array(
            'level_list' => $level_list,
            'level_num' => $level_num,
        );
        return $arr;
    }
}