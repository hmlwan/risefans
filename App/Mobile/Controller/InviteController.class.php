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
        $luckdraw_record_db = M('luckdraw_record');
        $mem_info = $db->get_info_by_id($member_id);
        /*徒弟*/
        $childs = $db->rechilds($mem_info['unique_code']);

        $childs_where = array(
            'm.member_id' => array('in', $childs)
        );
        $sort = "m.reg_time desc";

        $childs_list = $db->get_allinfo($childs_where, $sort);

        $get_gift_num = 0;
        foreach ($childs_list as $key => $value) {
            if (time() - $value['reg_time'] <= 60) {
                $childs_list[$key]['invite_time'] = "1分钟前";
            } elseif (time() - $value['reg_time'] <= 60 * 5) {
                $childs_list[$key]['invite_time'] = "5分钟前";
            } elseif (time() - $value['reg_time'] <= 60 * 10) {
                $childs_list[$key]['invite_time'] = "10分钟前";

            } elseif (time() - $value['reg_time'] <= 60 * 30) {
                $childs_list[$key]['invite_time'] = "半小时前";

            } elseif (time() - $value['reg_time'] <= 60 * 60) {
                $childs_list[$key]['invite_time'] = "1小时前";

            } elseif (time() - $value['reg_time'] <= 60 * 60 * 2) {
                $childs_list[$key]['invite_time'] = "2小时前";

            } elseif (time() - $value['reg_time'] <= 60 * 60 * 4) {
                $childs_list[$key]['invite_time'] = "4小时前";

            } elseif (time() - $value['reg_time'] <= 60 * 60 * 12) {
                $childs_list[$key]['invite_time'] = "12小时前";

            } elseif (time() - $value['reg_time'] <= 60 * 60 * 16) {
                $childs_list[$key]['invite_time'] = "16小时前";

            } elseif (time() - $value['reg_time'] <= 60 * 60 * 20) {
                $childs_list[$key]['invite_time'] = "20小时前";

            } elseif (time() - $value['reg_time'] <= 60 * 60 * 24) {
                $childs_list[$key]['invite_time'] = "1天前";
            } else {
                $childs_list[$key]['invite_time'] = date("Y-m-d", $value['reg_time']);
            }

            if($value['pid'] == $mem_info['unique_code']){ /*徒弟*/
                $childs_list[$key]['relation'] = "徒弟";
                $childs_list[$key]['level'] = 1;
            }else{/*徒孙*/
                $childs_list[$key]['relation'] = "徒孙";
                $childs_list[$key]['level'] = 2;
            }
            /*是否已领取奖励*/
            $luckdraw_info = $luckdraw_record_db
                ->where(array('member_id'=>$member_id,'type'=>2,'sub_id'=>$value['member_id']))->find();
            if($value['is_cert'] == 1 && $luckdraw_info){
                if($luckdraw_info['good_id']>0){
                    $childs_list[$key]['get_gift'] =$luckdraw_info['good_name']  ;
                    $get_gift_num = $get_gift_num + $luckdraw_info['num'];
                }else{
                    $childs_list[$key]['get_gift'] ="未抽中奖励"  ;

                }
            }elseif ($value['is_cert'] == 1 ){
                $childs_list[$key]['get_gift'] = "可领取"  ;
                $childs_list[$key]['luck_status'] = 1  ;
            }elseif ($value['is_cert'] == 0 ) {
                $childs_list[$key]['get_gift'] = "未实名";
                $childs_list[$key]['luck_status'] = 2  ;

            }
        }
        $this->assign('childs_list',$childs_list);
        $this->assign('get_gift_num',$get_gift_num);
        $this->assign('invite_num',count($childs_list));

        $this->display();
    }
    public function detail(){
        $this->display();
    }
    /*推广领取奖励*/
    public function get_reward(){

        $member_id = session('USER_KEY_ID');
        $level = I('level');
        if($level == 1){ /*一级推广*/
            $sign_luckdraw_title = $this->config['first_promotion'];
        }else if($level == 2){ /*二级推广*/
            $sign_luckdraw_title = $this->config['second_promotion'];
        }

        $sign_luckdraw_id = M('luckdraw_conf')->where(array('title'=>$sign_luckdraw_title))->getField('id');
        $micrtimes = microtime_float();
        $sign_luckdraw_count = M("luckdraw_conf_detail")->where(array("luckdraw_id"=>$sign_luckdraw_id))->count();

        /*推广记录*/
        $luckdraw_record_db = M('luckdraw_record');
        $luckdraw_record_data = array(
            'member_id' => $member_id,
            'type' => 2,
            'add_time' => time(),
            'type_id' => 0,
            'good_id' => -1,
            'type_name'=> '',
            'micrtimes'=> $micrtimes,
            'mod'=> 0,
        );
        /*存入消息列表*/
        $message_data = array(
            'title' => '推广奖励',
            'member_id' => $member_id,
            'type' => 3,
            'add_time' => time(),
            'is_read' => 0,
            'message_all_id' => 0,
        );

        if($sign_luckdraw_count>0){
            $mod = $micrtimes % $sign_luckdraw_count;
        }else{
            $luckdraw_record_db->add($luckdraw_record_data);
            $data['status'] = 0;
            $data['info'] = "很遗憾，未翻到商品卡";
            $message_data['content'] =  $data['info'];
            M('message')->add($message_data);
            $this->ajaxReturn($data);
        }
        $sign_luckdraw_list = M("luckdraw_conf_detail")->where(array("luckdraw_id"=>$sign_luckdraw_id))->select();

        $luckdraw_record_data['mod'] = $mod;
        if($mod == 0){ /*整除默认+1*/
            $mod = $mod + 1;
        }
        if(!empty($sign_luckdraw_list[$mod-1])){
            $luckdraw_num = $sign_luckdraw_list[$mod-1]['num'];
            /*查询对应商品卡*/
            $good_info = M("goods")->where(array('status'=>1,'luckdraw_num'=>$luckdraw_num))->find();
            if(!$good_info){
                $luckdraw_record_db->add($luckdraw_record_data);
                $data['status'] = 0;
                $data['info'] = "很遗憾，未翻到商品卡";
                $message_data['content'] =  $data['info'];
                M('message')->add($message_data);
                $this->ajaxReturn($data);
            }
            $luckdraw_record_data["type_id"] = $good_info['type_id'];
            $luckdraw_record_data["type_name"] = $good_info['type_name'];
            $luckdraw_record_data["good_id"] = $good_info['id'];
            $luckdraw_record_data["good_name"] = $good_info['good_name'];
            $luckdraw_record_data["price"] = $good_info['price'];
            $luckdraw_record_data["num"] = 1;
            $res = $luckdraw_record_db->add($luckdraw_record_data);
            if($res){
                /*存入商品卡卷*/
                $member_goods_info = M('member_goods')
                    ->where(array('member_id'=>$member_id,'good_id'=>$good_info['id']))
                    ->find();
                $mem_goods_data = array(
                    'member_id' => $member_id,
                    'good_id' => $good_info['id'],
                    'price' => $good_info['price'],
                    'good_name' => $good_info['good_name'],
                    'add_time' => time()
                );
                if($member_goods_info){
                    $mem_goods_data['num'] = $member_goods_info['num'] + 1;
                    $mem_goods_data['valid_num'] = $member_goods_info['valid_num'] + 1;
                    $mem_res = M('member_goods')->where(array('id'=>$member_goods_info['id']))->save();
                }else{
                    $mem_goods_data['num'] = 1;
                    $mem_goods_data['valid_num'] = 1;
                    $mem_res = M('member_goods')->add($mem_goods_data);
                }
                /*商品卡卷详情*/
                $mem_good_detail_data = array(
                    'member_id' => $member_id,
                    'type_id' => $good_info['type_id'],
                    'type_name' => $good_info['type_name'],
                    'good_id' => $good_info['id'],
                    'good_name' => $good_info['good_name'],
                    'num' => 1,
                    'is_exchange' => $good_info['is_exchange'],
                    'price' => $good_info['price'],
                    'origin_type' => 3,
                    'create_time' => time(),
                );
                M('member_goods_detail')->add($mem_good_detail_data);
            }

            $data['status'] = 1;
            $data['info'] = "恭喜你翻到<span>".$good_info['good_name']."<span>商品卡";
            $data['goods_name'] = $good_info['good_name'];
            $message_data['content'] = $data['info'];
            M('message')->add($message_data);
            $this->ajaxReturn($data);
        }else{
            $luckdraw_record_db->add($luckdraw_record_data);
            $data['status'] = 0;
            $data['info'] = "很遗憾，未翻到商品卡";
            $message_data['content'] = "很遗憾，未翻到商品卡";
            M('message')->add($message_data);
            $this->ajaxReturn($data);
        }
    }
}