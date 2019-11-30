<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class BonusController extends HomeController
{

    public function _initialize(){
        parent::_initialize();
    }
    public function test(){
        $this->display();
    }
    //空操作
    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:face_to_face');
    }

    public function index(){

        $bonus_record_db = M('bonus_record');
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        /*分红配置*/
        $bonus_conf = M("bonus_config")->where('1=1')->find();
        $reward_num = number_format($bonus_conf['reward_num'],$bonus_conf['decimal_num'],'.','');
        $reward_cur_name = D('currency')->get_cur_name($bonus_conf['currency_id']);

        $this->assign('reward_num',$reward_num);
        $this->assign('reward_cur_name',$reward_cur_name);
        $this->assign('bonus_conf',$bonus_conf);

        $member_info = $db->get_info_by_id($member_id);
        $this->assign('member_info',$member_info);

        /*累积发放金币*/
        $sum_jb_num = $bonus_record_db->where(array('currency_id'=>$bonus_conf['currency_id']))->sum('num');
        $zh_sum_jb_num = number_format($sum_jb_num/10000,2,'.','');
        if($zh_sum_jb_num >=1){
            $sum_jb_num = $zh_sum_jb_num.'万';
        }else{
            $sum_jb_num = number_format($sum_jb_num,0,'.','');
        }
        $this->assign('sum_jb_num',$sum_jb_num);


        /*我的凭证*/
        $bonus_voucher_num = M("vip_level_config")->where(array('type'=>$member_info['vip_level']))->getField('bonus_voucher_num');

        $this->assign('bonus_voucher_num',$bonus_voucher_num);

        /*我瓜分的收益*/
        $mem_last_record = $bonus_record_db
            ->where(array('member_id'=>$member_id,
                'currency_id'=>$bonus_conf['currency_id'])
            )->order('add_time desc')
            ->find();
        /*间隔时间*/
        $interval_hours = $bonus_conf['interval_hours'];
        $accumulate_hours = $bonus_conf['accumulate_hours'];
        $interval_second = $interval_hours *3600;
        $accumulate_second = $accumulate_hours *3600;
        /*剩余秒数*/
        $left_second = 0;
        /*已过有效秒数*/
        $during_second = 0;

        $is_receive = 0;
        $xc_second = time() - $mem_last_record['add_time'];
        $xc_second_next = time() - $mem_last_record['next_receive_time'];

        if($mem_last_record){
            if($xc_second < $interval_second){ /*小于间隔时间*/
                $left_second = 0;
            }else{ /*大于间隔时间*/
                if($xc_second_next>=$accumulate_second){
                    $during_second = $accumulate_second;
                    $left_second = 0;
                }else{
                    $is_receive = 1;
                    $during_second = $xc_second_next;
                    $left_second = $accumulate_second - $xc_second_next;
                }

            }
        }else{
            $x = time()-$member_info['reg_time'];
            $during_second = $x;

            if($x>=$accumulate_second){
                $during_second = $accumulate_second;
                $left_second = 0;
            }else{
                $left_second = $accumulate_second - $x;
            }
            $is_receive = 1;
        }

        $mem_jb_sum_num = $during_second * $reward_num * $bonus_voucher_num;

        $mem_jb_sum_num = number_format($mem_jb_sum_num,$bonus_conf['decimal_num'],'.','');
        $this->assign('mem_jb_sum_num',$mem_jb_sum_num);
        $this->assign('left_second',$left_second);
        $this->assign('is_receive',$is_receive);


        /*顶部随机领取*/
        $rand_head = rand(1,12);

        $rand_mem_phone = randomMobile(1);
        $rand_mem_phone = substr_replace ($rand_mem_phone[0],"****",3,4);
        $rand_mem['rand_mem_phone'] = $rand_mem_phone;
        $rand_mem['receive_time'] = time();
        $rand_mem['rand_head'] = $rand_head;
        $this->assign('rand_mem',$rand_mem);


        $this->display();
    }
    /*领取分红*/
    public function receive(){

        $bonus_record_db = M('bonus_record');
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        $member_info = $db->get_info_by_id($member_id);

        /*我的凭证*/
        $bonus_voucher_num = M("vip_level_config")->where(array('type'=>$member_info['vip_level']))->getField('bonus_voucher_num');
        /*分红配置*/
        $bonus_conf = M("bonus_config")->where('1=1')->find();
        $reward_num = number_format($bonus_conf['reward_num'],$bonus_conf['decimal_num'],'.','');

        /*我瓜分的收益*/
        $mem_last_record = $bonus_record_db
            ->where(array('member_id'=>$member_id,
                    'currency_id'=>$bonus_conf['currency_id'])
            )->order('add_time desc')
            ->find();
        /*间隔时间*/
        $interval_hours = $bonus_conf['interval_hours'];
        $accumulate_hours = $bonus_conf['accumulate_hours'];
        $interval_second = $interval_hours *3600;
        $accumulate_second = $accumulate_hours *3600;
        /*剩余秒数*/
        $left_second = 0;
        /*已过有效秒数*/
        $during_second = 0;

        $is_receive = 0;
        $xc_second = time() - $mem_last_record['add_time'];
        $xc_second_next = time() - $mem_last_record['next_receive_time'];

        if($mem_last_record){
            if($xc_second < $interval_second){ /*小于间隔时间*/

                $next_receive_date = date("H:i",$mem_last_record['next_receive_time']);
                $data['status']= 0;
                $data['info']="请".$next_receive_date.'后来领取';
                $this->ajaxReturn($data);
                $left_second = 0;
            }else { /*大于间隔时间*/
                if($xc_second_next>=$accumulate_second){
                    $during_second = $accumulate_second;
                    $left_second = 0;
                }else{
                    $is_receive = 1;
                    $during_second = $xc_second_next;
                    $left_second = $accumulate_second - $xc_second_next;
                }
            }
        }else{
            /*刚注册按注册时间起*/
            $xc_second = time() - $member_info['reg_time'];
            $during_second = $xc_second;
            $left_second = 0;
            if($xc_second >= $accumulate_second){
                $during_second = $accumulate_second;
            }else{
                $left_second = $accumulate_second - $during_second;
            }
            $is_receive = 1;
        }

        $bonus_reward_num = $during_second * $reward_num * $bonus_voucher_num;
        $bonus_reward_num = number_format($bonus_reward_num,$bonus_conf['receive_decimal_num'],'.','');
        if($bonus_reward_num < $bonus_conf['min_receive_num']){
            $data['status']= 0;
            $data['info']="最少领取".$bonus_conf['min_receive_num'];
            $this->ajaxReturn($data);
        }

        $bonus_data = array(
            'currency_id' => $bonus_conf['currency_id'],
            'num' => $bonus_reward_num,
            'member_id' => $member_id,
            'accumulate_second' => $during_second,
            'total_accumulate_second' => $during_second + $mem_last_record['total_accumulate_second'],
            'add_time' => time(),
            'level' => $member_info['vip_level'],
            'bonus_voucher_num' => $bonus_voucher_num,
        );
        /*加币*/
        $cur_r = D("currency")->mem_inc_cur($bonus_conf['currency_id'],$bonus_reward_num);
        if(!$cur_r){
            $data['status']= 0;
            $data['info']="领取失败";
            $this->ajaxReturn($data);
        }
        $bonus_record_r = $bonus_record_db->add($bonus_data);
        $balance = D('currency')->mem_cur_num($bonus_conf['currency_id'],$member_id);

        /*统计*/
        M("trade")->add(array(
            "member_id" => $member_id,
            "currency_id" => $bonus_conf['currency_id'],
            "num" => $bonus_reward_num,
            "content" => "分红领取".$bonus_reward_num.'币',
            "type" => 1,
            "trade_type" => 9,
            'add_time'=> time(),
            'balance' => $balance,
            'oldbalance' => $balance + $bonus_reward_num,
        ));

        /*返利*/
        /*父级一级*/
        $fa_info1 = D('member')->where(array('phone'=>$member_info['pid']))->find();
        if($fa_info1){
            $fa_info1_vip_level = D('member')->get_vip_level($fa_info1['member_id']);
            $fa1_vip_level = M('vip_level_config')->where(array('type'=>$fa_info1_vip_level))->find();

            /*父级增加下级购买vip返利币*/
            if($fa1_vip_level){
                $fa1_rebate_num = number_format($bonus_reward_num * $fa1_vip_level['sub_receive_bonus_rebate'],0,'.','');
                $fa_r1 = D('currency')->mem_inc_cur($bonus_conf['currency_id'],$fa1_rebate_num,$fa_info1['member_id']);
                $f1_balance = D('currency')->mem_cur_num($bonus_conf['currency_id'],$fa_info1['member_id']);
                /*邀请记录*/
                M("invite_record")->add(array(
                    'member_id' => $fa_info1['member_id'],
                    'currency_id' => $bonus_conf['currency_id'],
                    'num' => $fa1_rebate_num,
                    'sub_member_id' => $member_id,
                    'content' => "一级(".$member_id.")领取分红返利奖励".$fa1_rebate_num.'币',
                    'add_time' => time(),
                    'level' => 1,
                    'type' => 2,
                    'is_cert' => 2,
                ));
                /*统计*/
                M('trade')->add(array(
                    'member_id' => $fa_info1['member_id'],
                    'num' => $fa1_rebate_num,
                    'currency_id' => $bonus_conf['currency_id'],
                    'content' => "一级(".$member_id.")领取分红返利奖励".$fa1_rebate_num.'币',
                    'type' => 1,
                    'trade_type' => 5,
                    'add_time' => time(),
                    'balance' => $f1_balance,
                    'oldbalance' => $f1_balance + $fa1_rebate_num,
                ));
            }
        }
        /*父级二级*/
        $fa_info2 = D('member')->where(array('phone'=>$fa_info1['pid']))->find();
        if($fa_info2){
            $fa_info2_vip_level = D('member')->get_vip_level($fa_info2['member_id']);
            $fa2_vip_level = M('vip_level_config')->where(array('type'=>$fa_info2_vip_level))->find();

            /*父级增加下级购买vip返利币*/
            if($fa2_vip_level){
                $fa2_rebate_num = number_format($bonus_reward_num * $fa2_vip_level['sub_receive_bonus_rebate'],0,'.','');
                $fa_r2 = D('currency')->mem_inc_cur($bonus_conf['currency_id'],$fa2_rebate_num,$fa_info2['member_id']);
                /*邀请记录*/
                M("invite_record")->add(array(
                    'member_id' => $fa_info2['member_id'],
                    'currency_id' => $bonus_conf['currency_id'],
                    'num' => $fa2_rebate_num,
                    'sub_member_id' => $member_id,
                    'content' => "二级(".$member_id.")领取分红返利奖励".$fa2_rebate_num.'币',
                    'add_time' => time(),
                    'level' => 2,
                    'type' => 2,
                    'is_cert' => 2,
                ));
                $f2_balance = D('currency')->mem_cur_num($bonus_conf['currency_id'],$fa_info2['member_id']);

                /*统计*/
                M('trade')->add(array(
                    'member_id' => $fa_info2['member_id'],
                    'num' => $fa2_rebate_num,
                    'currency_id' => $bonus_conf['currency_id'],
                    'content' => "二级(".$member_id.")领取分红返利奖励".$fa2_rebate_num.'币',
                    'type' => 1,
                    'trade_type' => 5,
                    'add_time' => time(),
                    'balance' => $f2_balance,
                    'oldbalance' => $f2_balance + $fa2_rebate_num,
                ));
            }
        }

        $data['status']= 1;
        $data['info']="领取成功";
        $data['data']= array(
            'reward_num' => $bonus_reward_num,
            'cur_name' =>D('currency')->get_cur_name($bonus_conf['currency_id'])
        );
        $this->ajaxReturn($data);
    }

    public function record(){

        $bonus_record_db = M('bonus_record');
        $member_id = session('USER_KEY_ID');
        $bonus_conf = M("bonus_config")->where('1=1')->find();

        $list = $bonus_record_db->where(array('member_id'=>$member_id))->order("add_time desc")->select();
        foreach ($list as &$value){
            $value["num"] = number_format($value['num'],$bonus_conf['receive_decimal_num'],'.','');
        }
        $this->assign('list',$list);
        $this->display();
    }
    /*什么是权证*/
    public function bonus_voucher(){
        $this->display();
    }
    /*k线 每日发放分红 7天*/
    public function get_k_data(){

        $bonus_record_db = M('bonus_record');
        $end_time = time();
        $start_time = strtotime("-7 day",strtotime(date("Y-m-d 0:0:0",time())));

        $where = array(
            'add_time' =>array('between',array($start_time,$end_time))
        );
        $list = $bonus_record_db->where($where)->order('add_time desc')->select();
        $arr = array();
        foreach ($list as $value){
            $k = date("m-d",$value['add_time']);
            $arr[$k][] = $value['num'];
        }

        ksort($arr);
        $k_data = array();

        foreach ($arr as $key=> $v){

            $xh_num = number_format(array_sum($v)/10000,2,'.','');
            $xh_num1 = $xh_num.'万';

            $k_data[] = array(
                'value' => $xh_num,
                'date' => $key,
                'name' => $xh_num1
            );
        }
        $data['status']= 1;
        $data['info']="成功";
        $data['data']= $k_data;
        $this->ajaxReturn($data);
    }

}