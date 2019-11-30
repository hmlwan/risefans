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
        $this->display('Public:face_to_face');
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
            $inc_balance = D('currency')->mem_cur_num($vip_level['close_vip_reward_cur_id'],$member_id);

            M("trade")->add(array(
                'member_id' =>$member_id,
                'num' =>$vip_level['close_vip_reward_num'],
                'currency_id' =>$vip_level['close_vip_reward_cur_id'],
                'content' => '关闭vip',
                'type' => 1,
                'trade_type' => 8,
                'add_time' =>time(),
                'balance' => $inc_balance,
                'oldbalance' => $inc_balance + $vip_level['close_vip_reward_num'],
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
        $member_id = session('USER_KEY_ID');

        $vip_record_db = M("vip_record");
        $list = $vip_record_db->where(array('member_id'=>$member_id))->order('id desc')->select();
        foreach ($list as &$value){
            $value["currency_en"] = D('currency')->get_cur_en($value['currency_id']);
        }
        $this->assign('list',$list);
        $this->display();
    }
    public function buy_vip(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        $mem_info = $db->get_info_by_id($member_id);

        $vip_level_db = M("vip_level_config");
        $vip_level = $vip_level_db->order("type asc")->select();
        foreach ($vip_level as &$value){
            $value['sub_receive_bonus_rebate'] =  $value['sub_receive_bonus_rebate']*100;
            $value['sub_luckdraw_cur_name'] =  D('currency')->get_cur_name($value['sub_luckdraw_cur_id']);
            $value['close_vip_reward_cur_en'] =  D('currency')->get_cur_en($value['close_vip_reward_cur_id']);
            $value['sale_cur_en'] =  D('currency')->get_cur_en($value['sale_cur_id']);
        }

        $this->assign('vip_level',$vip_level);
        $this->assign('mem_info',$mem_info);
        $this->display();
    }
    /*购买vip操作*/
    public function buy_vip_op(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        $vip_level_db = M("vip_level_config");
        $id = I('id');
        $mem_info = $db->get_info_by_id($member_id);

        if(!$id){
            $data['status']= 0;
            $data['info']="参数错误";
            $this->ajaxReturn($data);
        }
        if($mem_info['vip_level']>1){
            $data['status']= 0;
            $data['info']="已是VIP".$mem_info['vip_level'].',请关闭VIP再购买';
            $this->ajaxReturn($data);
        }

        /*vip等级信息*/
        $vip_level = $vip_level_db->where(array('id'=>$id))->find();

        $sale_cur_num = $vip_level['sale_cur_num'];
        $sale_cur_id = $vip_level['sale_cur_id'];
        /*用户币种数量*/
        $mem_cur = D("currency")->mem_cur($vip_level['sale_cur_id']);

        if($mem_cur['num'] < $sale_cur_num){
            $data['status']= 0;
            $data['info']="币种数量不足";
            $this->ajaxReturn($data);
        }
        /*购买减币*/
        $cur_db = D('currency');
        $r = $cur_db->mem_dec_cur($sale_cur_id,$sale_cur_num);
        if(!$r){
            $data['status']= 0;
            $data['info']="兑换失败";
            $this->ajaxReturn($data);
        }
        /*更新vip*/
        $update_vip = M('member_info')
            ->where(array('member_id' => $member_id))
            ->save(array('vip_level'=>$vip_level['type']));
        if(!$update_vip){
            $data['status']= 0;
            $data['info']="兑换失败";
            $this->ajaxReturn($data);
        }
        /*购买vip记录*/
        M("vip_record")->add(array(
            'member_id' => $member_id,
            'num' => $sale_cur_num,
            'type' => 2,
            'currency_id' => $sale_cur_id,
            'level' => $vip_level['type'],
            'stype' => 1,
            'add_time' => time(),
        ));
        /*统计*/
        $dec_balance = D('currency')->mem_cur_num($sale_cur_id,$member_id);

        M('trade')->add(array(
            'member_id' => $member_id,
            'num' => $sale_cur_num,
            'currency_id' => $sale_cur_id,
            'content' => '购买vip'.$vip_level['type'],
            'type' => 2,
            'trade_type' => 7,
            'add_time' => time(),
            'balance' => $dec_balance,
            'oldbalance' => $dec_balance- $sale_cur_num,

        ));

        $data['status']= 1;
        $data['info']="兑换成功";
        $this->ajaxReturn($data);
    }

}