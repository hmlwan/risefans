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
        $this->assign('sign_conf',$sign_conf);
        /*红包配置*/
        $redpack_conf = M('hongbao_conf')->where("1=1")->find();
        $this->assign('redpack_conf',$redpack_conf);

        /*邀请配置*/
        $invite_conf = M("invite_conf")->where("1=1")->find();
        $this->assign('invite_conf',$invite_conf);

        /*购买贡献值配置*/
        $contribution_conf = M("contribution_conf")->where("1=1")->find();
        $this->assign('contribution_conf',$contribution_conf);

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

        $this->assign('contribution_conf',$contribution_conf);
        $this->assign('is_today_sign',$is_today_sign);
        $this->assign('is_continuity_sign',$is_continuity_sign);
        $this->assign('daily_record',$daily_record);
        $this->assign('tomorrow_sign_num',$tomorrow_sign_num);


        /*用户金币数量*/
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');
        $rmb_num = 0;

        if($mem_cur_num > 0){
            $rmb_num = number_format($mem_cur_num/100,2,'.','');
        }
        /*用户抽奖数*/
        $member_luckdraw_num = M('member_luckdraw_num')->where(array('member_id'=>$member_id))->find();

        $this->assign('member_ld_num',$member_luckdraw_num);


        $this->assign('rmb_num',$rmb_num);
        $this->assign('mem_cur_num',$mem_cur_num);
        $this->display();
    }

    public function sign(){
        $daily_num = I('post.daily_num');
        $daily_num = $daily_num + 1;
        $member_id = session('USER_KEY_ID');

        if($daily_num>7){
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
            $is_exist = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>$sign_cur_id))->find();
            if($is_exist){
                M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>$sign_cur_id))->setInc('num',$sign_num);
            }else{
                M('currency_user')->add(array('member_id'=>$member_id,'currency_id'=>$sign_cur_id,'num'=>$sign_num));
            }
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
    /*抽奖*/
    public function luckdraw(){
        $type = I('type');
        $member_id = session('USER_KEY_ID');
        /*用户抽奖数*/
       $mem_ld_num = M('member_luckdraw_num')->where(array('member_id'=>$member_id))->find();
       $task_ld_record_db = M('task_luckdraw_record');

       if($type == 1){
           $hongbao_conf =  M('hongbao_conf')->where("1=1")->find();
           $luckdraw_conf_id = $hongbao_conf['luckdraw_conf_id'];
            $use_luckdraw_num = $hongbao_conf['use_luckdraw_num'];
            $contribution_num = $hongbao_conf['add_contribution_num'];
            if($mem_ld_num['redpack_ld_num']<$use_luckdraw_num){
                $info['status'] = 0;
                $info['info'] ='抽奖数量不足';
                $this->ajaxReturn($info);
            }
            $field = "redpack_ld_num";
        }elseif ($type == 2){
           $invite_conf = M("invite_conf")->where("1=1")->find();
           $luckdraw_conf_id = $invite_conf['luckdraw_conf_id'];
           $use_luckdraw_num = $invite_conf['use_luckdraw_num'];
           $contribution_num = $invite_conf['add_contribution_num'];
           if($mem_ld_num['invite_ld_num']<$use_luckdraw_num){
               $info['status'] = 0;
               $info['info'] ='抽奖数量不足';
               $this->ajaxReturn($info);
           }
           $field = "invite_ld_num";

       }elseif ($type == 3){
           $contribution_conf = M("contribution_conf")->where("1=1")->find();
           $luckdraw_conf_id = $contribution_conf['luckdraw_conf_id'];
           $use_luckdraw_num = $contribution_conf['use_luckdraw_num'];
           $contribution_num = $contribution_conf['add_contribution_num'];
           if($mem_ld_num['buy_ld_num']<$use_luckdraw_num){
               $info['status'] = 0;
               $info['info'] ='抽奖数量不足';
               $this->ajaxReturn($info);
           }
           $field = "buy_ld_num";
       }

        $where = array(
            'luckdraw_id' => $luckdraw_conf_id
        );
        $cur_num = $this->get_luckdraw_num($where,$type);
        $cur_id = M('luckdraw_conf')->where(array('id'=>$luckdraw_conf_id))->getField('currency_id');
        $save_data = array(
            'member_id' => $member_id,
            'type' => $type,
            'currency_id' => $cur_id,
            'num' => $cur_num,
            'add_time' => time(),
            'use_luckdraw_num' => $use_luckdraw_num,
            'stype' => 2,
            'contribution_num' => $contribution_num,
        );
        $r = $task_ld_record_db->add($save_data);
        if(false == $r){
            $currency_name = M("currency")->where(array('currency_id'=>$cur_id))->getField('currency_name');
            /*减去抽奖数*/
            M('member_luckdraw_num')->where(array('member_id'=>$member_id))->setDec($field,$use_luckdraw_num);
            M('member_luckdraw_num')->where(array('member_id'=>$member_id))->setInc("contribute_num",$contribution_num);

            /*统计*/
            M('trade')->add(array(
                'member_id' => $member_id,
                'currency_id' => $cur_id,
                'num' => $cur_num,
                'add_time' => time(),
                'content' => '任务抽奖'.$cur_num.'币',
                'type' => 1,
                'trade_type' => 2,
            ));
            $info['status'] = 1;
            $info['info'] ='抽奖成功';
            $info['data'] = array(
                'cur_num' => $cur_num ? $cur_num : 0,
                'cur_name' => $currency_name ? $currency_name : "金币",
                'type' =>$type
            );
            $this->ajaxReturn($info);
        }else{
            $info['status'] = 0;
            $info['info'] ='抽奖失败';
            $this->ajaxReturn($info);
        }
    }
    /*抽奖方式*/
    public function get_luckdraw_num($where,$type){
        //抽奖项
        $member_id = session('USER_KEY_ID');

        $count = M('task_luckdraw_record')
            ->where(array('member_id'=>$member_id,'type'=>$type,'stype'=>2))
            ->count();
        $db = M('luckdraw_conf_detail');
        $luckdraw_count = $db->where($where)->count();
        $luckdraw_detail = $db->where($where)->order('id desc')->select();
        $get_num_k = $count + 1;
        if($count / $luckdraw_count >0){
            $get_num_k = ($count % $luckdraw_count) + 1;
        }
        $cur_num_info = $luckdraw_detail[$get_num_k];
        $cur_num = $cur_num_info['num'];
        return $cur_num ? $cur_num : 0;
    }
    /*查看我的奖品*/
    public function task_record(){
        $this->display();
    }
}