<?php
/**
 * Created by PhpStorm.
 * User: v_huizzeng
 * Date: 2019/10/6
 * Time: 22:00
 */

namespace Mobile\Controller;


class SysController extends HomeController
{

    public function _initialize(){
        parent::_initialize();
    }
    //空操作
    public function _empty(){
        header("HTTP/1.0 404 Not Found");
        $this->display('Public:404');
    }
    /*云链钱包*/
    public function wallet(){

        $db = D('currency_user');
        $member_id = session('USER_KEY_ID');
        $where = array(
            'member_id' => $member_id,
            'currency_id' => $this->config['set_user_currency'],
        );
        $currency_user = $db->where($where)->find();
        $this->assign('currency_user',$currency_user);
        $this->display();
    }
    /*签到*/
    public function sign(){
        if(IS_POST){
            $member_id = session('USER_KEY_ID');

            $sign_luckdraw_title = $this->config['sign_luckdraw_title'];
            $sign_luckdraw_id = M('luckdraw_conf')->where(array('title'=>$sign_luckdraw_title))->getField('id');
            $micrtimes = microtime_float();
            $sign_luckdraw_count = M("luckdraw_conf_detail")->where(array("luckdraw_id"=>$sign_luckdraw_id))->count();

            /*签到记录*/
            $luckdraw_record_db = M('luckdraw_record');
            $luckdraw_record_data = array(
                'member_id' => $member_id,
                'type' => 1,
                'add_time' => time(),
                'type_id' => 0,
                'good_id' => -1,
                'type_name'=> '',
                'micrtimes'=> $micrtimes,
                'mod'=> 0,
            );
            /*存入消息列表*/
            $message_data = array(
                'title' => '签到奖励',
                'member_id' => $member_id,
                'type' => 2,
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
                        'origin_type' => 2,
                        'create_time' => time(),
                    );
                    M('member_goods_detail')->add($mem_good_detail_data);
                }

                $data['status'] = 1;
                $data['info'] = "恭喜你翻到<span>".$good_info['good_name']."<span>商品卡";
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

    /*我的消息*/
    public function message(){

        $member_id = session('USER_KEY_ID');
        $db = M('message');
        if(IS_POST){
            $message_id = I('message_id');
            if($message_id){
                $res = $db->where(array('message_id'=>$message_id))->save(array('is_read'=>1));
                if($res){
                    $data['status'] = 1;
                    $data['info'] = "已读成功";
                    $this->ajaxReturn($data);
                }else{
                    $data['status'] = 0;
                    $data['info'] = "已读失败";
                    $this->ajaxReturn($data);
                }
            }

        }else{
            $list = $db->where(array('member_id'=>$member_id))
                ->order('is_read asc,add_time desc')
                ->select();
            $this->assign('list',$list);
            $this->display();
        }

    }


}