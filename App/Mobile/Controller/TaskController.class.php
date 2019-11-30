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
        $this->display('Public:face_to_face');
    }

    public function index()
    {
        $member_id = session('USER_KEY_ID');
        $mem_info = D("Member")->get_info_by_id($member_id);
        $mem_info["phone_hide"] = substr_replace($mem_info['phone'],'****',3,4);
        /*每日签到*/
        $sign_conf_db = M('sign_conf');
        $sign_conf = $sign_conf_db->where("1=1")->find();
        $this->assign('sign_conf',$sign_conf);
        /*抽奖配置*/
        $task_conf = M('task_conf')->where("1=1")->find();
        $task_conf['task_luckdraw_use_cur_name'] = D("currency")->get_cur_name($task_conf['task_luckdraw_use_cur_id']);

        $this->assign('task_conf',$task_conf);

        /*邀请配置*/
        $invite_conf = M("invite_conf")->where("1=1")->find();
        $this->assign('invite_conf',$invite_conf);

        /*每日签到记录*/
        $daily_luckdraw_db = M('daily_luckdraw');
        $daily_record =  $daily_luckdraw_db
            ->where(array('member_id'=>$member_id))
            ->order("add_time desc")
            ->limit(1)
            ->find();

        /*今日是否签到*/
        $is_today_sign = 0;
        $is_continuity_sign = 1;

        if(date("Y-m-d",$daily_record['add_time']) == date('Y-m-d',time())){
            $is_today_sign = 1;
        }else{
            /*是否连续签到*/
            if(date("Y-m-d",$daily_record['add_time']) != date("Y-m-d",strtotime("-1 day"))){
                $is_continuity_sign = 0;
                $daily_record['daily_num'] = 0;
            }
        }
        /*明天签到金币*/
        $tomorrow_sign_field = 'num_'.($daily_record['daily_num']+1);

        if( $daily_record['daily_num'] == 7){
            $tomorrow_sign_field = 'num_1';
        }
        $tomorrow_sign_num = $sign_conf[$tomorrow_sign_field];

        $this->assign('is_today_sign',$is_today_sign);
        $this->assign('is_continuity_sign',$is_continuity_sign);
        $this->assign('daily_record',$daily_record);
        $this->assign('tomorrow_sign_num',$tomorrow_sign_num);


        /*用户金币数量*/
        $mem_jb_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');

        /*用户莱特币数量*/
        $mem_ltb_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>2))->getField('num');
        $mem_jb_cur_num = number_format($mem_jb_cur_num,2,'.','');
        $mem_ltb_cur_num = number_format($mem_ltb_cur_num,3,'.','');

        /*随机抽中金币*/
        $rand_arr = array();

        $rand_num = 30;
        $rand_mem_phone_list = randomMobile($rand_num);
        foreach ($rand_mem_phone_list as $phone){
            $rand_mem_phone = substr_replace ($phone,"****",3,4);
            $rand_arr[] = array(
                'rand_mem_phone' => $rand_mem_phone,
                'rand_num' => $rand_num,
            );
        }

        $this->assign('rand_arr',$rand_arr);

        $this->assign('mem_jb_cur_num',$mem_jb_cur_num);
        $this->assign('mem_ltb_cur_num',$mem_ltb_cur_num);
        $this->display();
    }

    public function sign(){
        $daily_num = I('post.daily_num');
        $daily_num = $daily_num + 1;
        $member_id = session('USER_KEY_ID');

        if($daily_num > 7){
            $daily_num = 1;
        }
        $sign_conf_db = M('sign_conf');
        $sign_conf = $sign_conf_db->where("1=1")->find();
        $sign_num = $sign_conf['num_'.$daily_num];
        $sign_cur_id = $sign_conf['cur_id_'.$daily_num];
        $db = M('daily_luckdraw');
        $add_data = array(
            'member_id' => $member_id,
            'currency_id' => $sign_cur_id,
            'num' => $sign_num,
            'add_time' => time(),
            'daily_num' => $daily_num,
        );
        $r = $db->add($add_data);
        if(false !== $r){
            D("currency")->mem_inc_cur($sign_cur_id,$sign_num);

            /*统计*/
            M('trade')->add(array(
                'member_id' => $member_id,
                'currency_id' => $sign_cur_id,
                'num' => $sign_num,
                'add_time' => time(),
                'content' => '每日签到'.$sign_num.'币',
                'type' => 1,
                'trade_type' => 1,
            ));
            $info['status'] = 1;
            $info['info'] ='签到成功';
            $this->ajaxReturn($info);
        }else{
            $info['status'] = 0;
            $info['info'] ='签到失败';
            $this->ajaxReturn($info);
        }
    }
    /*转盘抽奖*/
    public function luckdraw(){

        $member_id = session('USER_KEY_ID');
        $mem_info = D("Member")->get_info_by_id($member_id);
        $vip_level_db = M("vip_level_config");

        /*vip等级信息*/
        $vip_level = $vip_level_db->where(array('type'=>$mem_info['vip_level']))->find();

        /*抽奖配置*/
        $task_conf = M('task_conf')->where("1=1")->find();
        if(!$task_conf){
            $data['status']= 0;
            $data['info']="服务器繁忙,请稍后重试";
            $this->ajaxReturn($data);
        }
        $task_luckdraw_use_num = $task_conf['task_luckdraw_use_num'];
        $task_luckdraw_use_cur_id = $task_conf['task_luckdraw_use_cur_id'];
        $cur_db = D('currency');
        $luckdraw_id = $task_conf['luckdraw_conf_id'];
        $get_ld_info = M("luckdraw_conf")->where(array('id'=>$luckdraw_id))->find();

        $ld_cur_info = $this->get_luckdraw_num(array('luckdraw_id'=>$luckdraw_id));
        $ld_cur_num = $ld_cur_info['num'];
        /*用户币种数量*/
        $mem_cur = $cur_db->mem_cur($task_luckdraw_use_cur_id);

        if($mem_cur['num'] < $task_luckdraw_use_num){
            $data['status']= 0;
            $data['info']="抽奖币种数量不足";
            $this->ajaxReturn($data);
        }
        /*减*/
        $r1 = $cur_db->mem_dec_cur($task_luckdraw_use_cur_id,$task_luckdraw_use_num);

        if(!$r1){
            $data['status']= 0;
            $data['info']="抽奖失败";
            $this->ajaxReturn($data);
        }

        $ld_cur_id = $get_ld_info['currency_id'];

        $ld_cur_name = $cur_db->get_cur_name($ld_cur_id);
        /*加*/
        $r2 =  $cur_db->mem_inc_cur($ld_cur_id,$ld_cur_num);

        if(!$r2){
            $data['status']= 0;
            $data['info']="抽奖失败";
            $this->ajaxReturn($data);
        }
        /*抽奖记录*/
        M('task_luckdraw_record')->add(array(
            'member_id' => $member_id,
            'num' => $ld_cur_num,
            'currency_id' => $ld_cur_id,
            'add_time' => time(),
            'use_num' => $task_luckdraw_use_num,
            'use_cur_id' => $task_luckdraw_use_cur_id,
            'ld_detail_id' => $ld_cur_info['id'],
        ));
        /*统计*/
        $dec_balance = D('currency')->mem_cur_num($task_luckdraw_use_cur_id,$member_id);

        /*减*/
        M("trade")->add(array(
            'member_id' => $member_id,
            'num' => $task_luckdraw_use_num,
            'currency_id' => $task_luckdraw_use_cur_id,
            'content' => "转盘抽奖消耗".$task_luckdraw_use_num.'币',
            'type' => 2,
            'trade_type' => 2,
            'add_time' => time(),
            'balance' => $dec_balance,
            'oldbalance' => $dec_balance - $task_luckdraw_use_num,

            ));
        /*加*/
        $inc_balance = D('currency')->mem_cur_num($ld_cur_id,$member_id);

        M("trade")->add(array(
            'member_id' => $member_id,
            'num' => $ld_cur_num,
            'currency_id' => $ld_cur_id,
            'content' => "转盘抽奖奖励".$ld_cur_num.'币',
            'type' => 1,
            'trade_type' => 2,
            'add_time' => time(),
            'balance' => $inc_balance,
            'oldbalance' => $inc_balance - $ld_cur_num,

        ));
        /*返利*/
        $fa_info1 = D('member')->where(array('phone'=>$mem_info['pid']))->find();
        if($fa_info1){
            $fa_info1_vip_level = D('member')->get_vip_level($fa_info1['member_id']);
            $fa1_vip_level = M('vip_level_config')->where(array('type'=>$fa_info1_vip_level))->find();
            $fa1_num = $fa1_vip_level['sub_luckdraw_num'];
            $fa1_cur_id = $fa1_vip_level['sub_luckdraw_cur_id'];
            if($fa1_cur_id){
                /*父级增加下级购买vip返利币*/
                $fa_r1 = $cur_db->mem_inc_cur($fa1_cur_id,$fa1_num,$fa_info1['member_id']);
                $f1_balance = D('currency')->mem_cur_num($fa1_cur_id,$fa_info1['member_id']);

                M("invite_record")->add(array(
                    'member_id' => $fa_info1['member_id'],
                    'currency_id' => $fa1_cur_id,
                    'num' => $fa1_num,
                    'sub_member_id' => $member_id,
                    'content' => "下线抽奖返利".$fa1_num.'币',
                    'add_time' => time(),
                    'level' => 1,
                    'type' => 3,
                    'is_cert' => 2,
                ));
                /*统计*/
                M('trade')->add(array(
                    'member_id' => $fa_info1['member_id'],
                    'num' => $fa1_num,
                    'currency_id' => $fa1_cur_id,
                    'content' => "一级下线抽奖返利".$fa1_num.'币',
                    'type' => 1,
                    'trade_type' => 6,
                    'add_time' => time(),
                    'balance' => $f1_balance,
                    'oldbalance' => $f1_balance + $fa1_num,
                ));
            }

        }
        $fa_info2 = D('member')->where(array('phone'=>$fa_info1['pid']))->find();
        if($fa_info2){
            $fa_info2_vip_level = D('member')->get_vip_level($fa_info2['member_id']);
            $fa2_vip_level = M('vip_level_config')->where(array('type'=>$fa_info2_vip_level))->find();
            $fa2_num = $fa2_vip_level['sub_luckdraw_num'];
            $fa2_cur_id = $fa2_vip_level['sub_luckdraw_cur_id'];
            if($fa2_cur_id){
                /*父级增加下级购买vip返利币*/
                $fa_r2 = $cur_db->mem_inc_cur($fa2_cur_id,$fa2_num,$fa_info2['member_id']);
                $f2_balance = D('currency')->mem_cur_num($fa2_cur_id,$fa_info2['member_id']);

                M("invite_record")->add(array(
                    'member_id' => $fa_info2['member_id'],
                    'currency_id' => $fa2_cur_id,
                    'num' => $fa2_num,
                    'sub_member_id' => $member_id,
                    'content' => "二级下线抽奖返利".$fa2_num.'币',
                    'add_time' => time(),
                    'level' => 2,
                    'type' => 3,
                    'is_cert' => 2,
                ));
                /*统计*/
                M('trade')->add(array(
                    'member_id' => $fa_info2['member_id'],
                    'num' => $fa2_num,
                    'currency_id' => $fa2_cur_id,
                    'content' => "下线抽奖返利".$fa2_num.'币',
                    'type' => 1,
                    'trade_type' => 6,
                    'add_time' => time(),
                    'balance' => $fa_r2,
                    'oldbalance' => $fa_r2 + $fa2_num,

                    ));
            }
        }

        $jb_n = D("currency")->mem_cur($ld_cur_id);
        $jtb_n = D("currency")->mem_cur($task_luckdraw_use_cur_id);
        $info['status'] = 1;
        $info['info'] ='抽奖成功';
        $info['data'] = array(
            'cur_num' => $ld_cur_num ? $ld_cur_num : 0,
            'cur_name' => $ld_cur_name ? $ld_cur_name : "金币",
            'jb_n' => number_format($jb_n['num'],0,'',''),
            'jtb_n' => number_format($jtb_n['num'],'3','.',''),
        );
        $this->ajaxReturn($info);
    }
    /*抽奖方式*/
    public function get_luckdraw_num($where){
        //抽奖项
        $member_id = session('USER_KEY_ID');

        $count = M('task_luckdraw_record')
            ->where(array('member_id'=>$member_id))
            ->count();
        $db = M('luckdraw_conf_detail');
        $luckdraw_count = $db->where($where)->count();
        $luckdraw_detail = $db->where($where)->order('id asc')->select();
        $get_num_k = $count + 1;

        if($count / $luckdraw_count >=1){
            $get_num_k = ($count % $luckdraw_count) + 1;
        }
        $cur_num_info = $luckdraw_detail[$get_num_k-1];
//        $cur_num = $cur_num_info['num'];
        return $cur_num_info;
    }
    /*查看我的奖品*/
    public function task_record(){

        $db = M('task_luckdraw_record');
        $member_id = session('USER_KEY_ID');

        $list = $db->where(array('member_id'=>$member_id))->order('id desc')->select();
        foreach ($list as &$value){
            $value['currency_name'] = D('currency')->get_cur_name($value['currency_id']);
        }
        $this->assign('list',$list);
        $this->display();
    }
    public function test(){
        $this->display();
    }
}