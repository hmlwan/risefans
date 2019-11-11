<?php
namespace Mobile\Controller;
/**
 * Created by PhpStorm.
 * User: hmlwan521
 * Date: 2019/11/10
 * Time: 下午5:19
 */
class RedpackController extends HomeController
{
    public function  index(){
        /*红包配置*/
        $hb_config = D('hongbao_conf')->where("1=1")->find();
        $member_id = $_SESSION['USER_KEY_ID'];
        $mem_info = D("Member")->get_info_by_id($member_id);
        $vip_receive_num = 0;

        /*今日已领取红包*/
        $hongbao_ad_record_m = M('hongbao_ad_record');
        $receive_num_data = $hongbao_ad_record_m->where(array('member_id'=>$member_id))->select();
        $today_re = 0;
        $total_re = 0;
        $total_re_num = 0;
        $left_re = 0;
        if($receive_num_data){
            foreach ($receive_num_data as $r){
                $total_re = $total_re+1;
                if($r['add_time']>strtotime(date("Y-m-d 00:00:00",time())) && $r['add_time']<strtotime(date("Y-m-d 23:59:59",time()))){
                    $today_re = $today_re+1;
                    $total_re_num = $total_re_num + $r['num'];
                }
            }
        }
        if($mem_info["vip_level"] != 0){
            $field = "watch_num".$mem_info["vip_level"];
            $vip_receive_num = $hb_config[$field];
            $left_re = $vip_receive_num - $total_re;
            $left_re = $left_re <= 0?0:$left_re;
        }

        $this->assign("today_re",$today_re);
        $this->assign("total_re",$total_re);
        $this->assign("total_re_num",$total_re_num);
        $this->assign("left_re",$left_re);

        $this->assign("mem_info",$mem_info);
        $this->assign("hb_config",$hb_config);
        $this->assign("vip_receive_num",$vip_receive_num);
        $this->display();
    }

    /*观看广告*/
    public function watch_ad(){

        $hb_config = D('hongbao_conf')->where("1=1")->find();
        $member_id = $_SESSION['USER_KEY_ID'];

        /*随机得到金币*/
        $luckdraw_conf_m = M('luckdraw_conf');
        $luckdraw_conf_cur = $luckdraw_conf_m->where(array('id'=>$hb_config['luckdraw_conf_id']))->getField('currency_id');
        $luckdraw_conf_cur_name = M('currency')->where(array('currency_id'=>$luckdraw_conf_cur))->getField('currency_name');
        //抽奖项
        $luckdraw_count = M("luckdraw_conf_detail")->where(array('luckdraw_id'=>$hb_config['luckdraw_conf_id']))->count();
        $luckdraw_detail = M("luckdraw_conf_detail")->where(array('luckdraw_id'=>$hb_config['luckdraw_conf_id']))->select();


        /*用户金币数量*/
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>$luckdraw_conf_cur))->getField('num');
        $rmb_num = 0;

        if($mem_cur_num > 0){
            $rmb_num = number_format($mem_cur_num/100,2,'.','');
        }
        $hongbao_ad_record_m = M('hongbao_ad_record');
        $receive_num_count = $hongbao_ad_record_m->where(array('member_id'=>$member_id))->count();

        $get_num_k = $receive_num_count + 1;
        if($receive_num_count/$luckdraw_count >0){
            $get_num_k = ($receive_num_count%$luckdraw_count) + 1;
        }

        $cur_num = $luckdraw_detail[$get_num_k];

        /*随机广告*/
        $ad_m  = M("hongbao_ad");
        $ad_list = $ad_m->where(array('status'=>1))->order("rand()")->limit(1)->select();

        $this->assign("ad_list",$ad_list);
        $this->assign("mem_cur_num",$mem_cur_num);
        $this->assign("luckdraw_conf_cur_name",$luckdraw_conf_cur_name);
        $this->assign("luckdraw_conf_cur",$luckdraw_conf_cur);
        $this->assign("cur_num",$cur_num);
        $this->assign("rmb_num",$rmb_num);

        $this->display();
    }
    public function op_receive(){

        $currency_num = I('currency_num','','');
        $currency_id = I('currency_id','','');
        $hongbao_ad_id = I('hongbao_ad_id','','');

        $db = M("hongbao_ad_record");
        $member_id = $_SESSION['USER_KEY_ID'];

        if(!$currency_id){
            $info['status'] = -1;
            $info['info'] ='传入参数有误';
            $this->ajaxReturn($info);
        }

        $data = array(
            'member_id' => $member_id,
            'currency_id' => $currency_id,
            'num' => $currency_num,
            'add_time' => time(),
            'hongbao_ad_id' => $hongbao_ad_id,
        );
        $r  = $db->add($data);
        if($r){
            $info['status'] = 1;
            $info['info'] ='领取成功';
            $this->ajaxReturn($info);

        }else{
            $info['status'] = -1;
            $info['info'] ='领取失败';
            $this->ajaxReturn($info);
        }
    }

    /*红包记录*/
    public function  receive_redpack_record(){

        $db = M("hongbao_ad_record");
        $member_id = $_SESSION['USER_KEY_ID'];
        $list = $db->where(array('member_id'=>$member_id))->order("add_time desc")->select();
        $data = array();
        if($list){
            foreach ($list as $key => $value){
                $value['currency_name'] = M('currency')->where(
                    array('currency_id'=>$value['currency_id'])
                )->getField('currency_name');

                $date = date("Y-m-d",$value['add_time']);
                if($date == date("Y-m-d",time())){
                    $data['今日'][] = $value;
                }else{
                    $data[$date][] = $value;
                }

            }
        }

        $this->assign("data",$data);

        $this->display();
    }

}