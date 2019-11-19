<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class VipController extends HomeController
{

    public function _initialize(){
        parent::_initialize();
    }
    //空操作
    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:404');
    }

    public function index(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');

        $mem_info = $db->get_info_by_id($member_id);

        $vip_level_db = M("vip_level_config");
        $vip_level = $vip_level_db->where(array('type'=>$mem_info['vip_level']))->find();
        $vip_level['close_vip_reward_cur_name'] = D("currency")->get_cur_name($vip_level['close_vip_reward_cur_id']);

        $this->assign('mem_info',$mem_info);
        $this->assign('vip_level',$vip_level);
        $this->display();
    }
    /*关闭vip*/
    public function close_vip(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');

        $mem_info = $db->get_info_by_id($member_id);
        if($mem_info['vip_level'] == 1){
            $data['status']= 0;
            $data['info']="已是vip1等级";
            $this->ajaxReturn($data);
        }
        $vip_level_db = M("vip_level_config");
        $vip_level = $vip_level_db->where(array('type'=>$mem_info['vip_level']))->find();
        $r = M('member_info')->where(array('member_id'=>$member_id))->save(array('vip_level'=>1));
        if($r){
            /*增加币种*/
            D("currency")->mem_inc_cur($vip_level['close_vip_reward_cur_id'],$vip_level['close_vip_reward_num']);

            /*vip记录*/
            M("vip_record")->add(array(
                'member_id' =>$member_id,
                'num' =>$vip_level['close_vip_reward_num'],
                'currency_id' =>$vip_level['close_vip_reward_cur_id'],
                'type' =>1,
                'level' =>1,
                'stype' =>2,
                'add_time' =>time(),
            ));
            /*统计*/
            M("trade")->add(array(
                'member_id' =>$member_id,
                'num' =>$vip_level['close_vip_reward_num'],
                'currency_id' =>$vip_level['close_vip_reward_cur_id'],
                'content' => '关闭vip',
                'type' => 1,
                'trade_type' => 8,
                'add_time' =>time(),
            ));
            $data['status']= 1;
            $data['info']="关闭成功";
            $this->ajaxReturn($data);
        }else{
            $data['status']= 0;
            $data['info']="关闭失败";
            $this->ajaxReturn($data);
        }
    }
    public function vip_record(){
        $this->display();
    }
    public function buy_vip(){

        


        $this->display();
    }
}