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
    //空操作
    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:face_to_face');
    }
    public function  index(){
        /*红包配置*/
        $hb_config = D('hongbao_conf')->where("1=1")->find();
        $member_id = $_SESSION['USER_KEY_ID'];
        $mem_info = D("Member")->get_info_by_id($member_id);
        $hongbao_ad_record_m = M('hongbao_ad_record');
        $receive_hongbao_num = M("vip_level_config")->where(array('type'=>$mem_info['vip_level']))->getField('receive_hongbao_num');
//        $hongbao_num = $hb_config['hongbao_num'];
        $this->assign("receive_hongbao_num",$receive_hongbao_num);
        /*权威今日总共领取红包数量*/
        $td_total_where['add_time'] =
           array('between',array(strtotime(date("Y-m-d 00:00:00",time())),strtotime(date("Y-m-d 23:59:59",time())))
        );

        $td_sum_receive_num = $hongbao_ad_record_m->where($td_total_where)->count();

        $this->assign("td_sum_receive_num",$td_sum_receive_num);

        $num2 =  round($receive_hongbao_num/2);
        $num1 =  round($num2/2);
        $num3 =  $num2+$num1;
        $this->assign("num1",$num1);
        $this->assign("num2",$num2);
        $this->assign("num3",$num3);
        $this->assign("num4",$receive_hongbao_num);


        /*今日已领取红包*/
        $receive_num_data = $hongbao_ad_record_m->where(array('member_id'=>$member_id))->select();
        $sum_num = 0;
        $sum_re = 0;
        $today_re = 0;
        $today_re_sum = 0;
        $left_re = 0;
        if($receive_num_data){
            foreach ($receive_num_data as $r){
                $sum_re = $sum_re + 1;
                $sum_num = $sum_num+$r['num'];
                if($r['add_time']>strtotime(date("Y-m-d 00:00:00",time())) && $r['add_time']<strtotime(date("Y-m-d 23:59:59",time()))){
                    $today_re = $today_re + 1;
                    $today_re_sum = $today_re_sum + $r['num'];
                }
            }
        }

        /*比例*/
        $hb_rate = $today_re/$receive_hongbao_num;
        $rate_num = round($hb_rate*240);
        if($rate_num == 0 && $hb_rate > 0){
            $rate_num = 1;
        }
        if($rate_num == 1 && $hb_rate < 1){
            $rate_num = 240-1;
        }

        $this->assign("rate_num",$rate_num);
        $left_re = $receive_hongbao_num - $today_re;
        $this->assign("today_re",$today_re);
        $this->assign("sum_re",$sum_re);
        $this->assign("sum_num",$sum_num);
        $this->assign("left_re",$left_re);

        $this->assign("mem_info",$mem_info);
        $this->assign("hb_config",$hb_config);
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
        $luckdraw_detail = M("luckdraw_conf_detail")->where(array('luckdraw_id'=>$hb_config['luckdraw_conf_id']))->order('id asc')->select();

        /*用户金币数量*/
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>$luckdraw_conf_cur))->getField('num');
        $rmb_num = 0;
        $mem_cur_num = number_format($mem_cur_num,0,'.','');
        if($mem_cur_num > 0){
            $rmb_num = number_format($mem_cur_num/100,2,'.','');
        }
        $hongbao_ad_record_m = M('hongbao_ad_record');
        $receive_num_count = $hongbao_ad_record_m->where(array('member_id'=>$member_id))->count();

        $get_num_k = $receive_num_count + 1;
        if($receive_num_count/$luckdraw_count >=1){
            $get_num_k = ($receive_num_count%$luckdraw_count) + 1;
        }

        $cur_num_info = $luckdraw_detail[$get_num_k-1];
        $cur_num = $cur_num_info['num'];
        $ld_detail_id = $cur_num_info['id'];

        /*随机广告*/
        $ad_m  = M("hongbao_ad");
        $ad_list = $ad_m->where(array('status'=>1))->order("rand()")->limit(1)->select();

        /*阅读数*/
        $ad_detail_r = M("hongbao_ad_read_detail")->where(array('member_id'=>$member_id,'hb_ad_id'=>$ad_list[0]['id']))->find();
        if($ad_detail_r){
            M("hongbao_ad_read_detail")->where(array('member_id'=>$member_id,'hb_ad_id'=>$ad_list[0]['id']))->setInc('watch_num',1);
        }else{
            M("hongbao_ad_read_detail")->add(array('member_id'=>$member_id,'hb_ad_id'=>$ad_list[0]['id'],'watch_num'=>1));
        }

        $this->assign("ad_list",$ad_list);
        $this->assign("mem_cur_num",$mem_cur_num);
        $this->assign("luckdraw_conf_cur_name",$luckdraw_conf_cur_name);
        $this->assign("luckdraw_conf_cur",$luckdraw_conf_cur);
        $this->assign("cur_num",$cur_num);
        $this->assign("rmb_num",$rmb_num);
        $this->assign("ld_detail_id",$ld_detail_id);

        $this->display();
    }
    public function op_receive(){

        $currency_num = I('currency_num','','');
        $currency_id = I('currency_id','','');
        $hongbao_ad_id = I('hongbao_ad_id','','');
        $ld_detail_id = I('ld_detail_id','','');

        $db = M("hongbao_ad_record");
        $member_id = $_SESSION['USER_KEY_ID'];

        if(!$currency_id){
            $info['status'] = -1;
            $info['info'] ='传入参数有误';
            $this->ajaxReturn($info);
        }
        $r = D("currency")->mem_inc_cur($currency_id,$currency_num);
        if(!$r){
            $info['status'] = -1;
            $info['info'] ='领取失败';
            $this->ajaxReturn($info);
        }
        $data = array(
            'member_id' => $member_id,
            'currency_id' => $currency_id,
            'num' => $currency_num,
            'add_time' => time(),
            'hongbao_ad_id' => $hongbao_ad_id,
            'ld_detail_id' => $ld_detail_id,
        );
        $r1  = $db->add($data);
        if($r1){
            $inc_balance = D('currency')->mem_cur_num($currency_id,$member_id);

            /*统计*/
            M('trade')->add(array(
                'member_id' => $member_id,
                'currency_id' => $currency_id,
                'num' => $currency_num,
                'add_time' => time(),
                'content' => '领取红包'.$currency_num.'币',
                'type' => 1,
                'trade_type' => 4,
                'balance' => $inc_balance,
                'oldbalance' => $inc_balance + $currency_num,

            ));

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
    public function make_money(){
        $this->display();
    }
}