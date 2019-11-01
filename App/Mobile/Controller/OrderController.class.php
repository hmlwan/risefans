<?php
namespace Mobile\Controller;
use Common\Controller\CommonController;
class OrderController extends HomeController {
 	public function _initialize(){
 		parent::_initialize();

 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	public function mem_goods(){

        $member_id = session('USER_KEY_ID');
        $db = M('member_goods');

        $list = $db->where(array('member_id'=>$member_id))->order("add_time desc")->select();
        foreach ($list as $key => $value){
            $good_info = M('goods')->where(array('id'=>$value['good_id'] ))->field("is_exchange,status,good_name,logo,price")->find();
            if($good_info){
                $list[$key]['status'] =  $good_info['status'];
                if($good_info['is_exchange'] == 1){
                    $list[$key]['is_exchange'] = 1;
                }else{
                    $list[$key]['is_exchange'] = 0;
                }
                $list[$key]['good_name'] = $good_info['good_name'];
                $list[$key]['logo'] = $good_info['logo'];
                $list[$key]['price'] = $good_info['price'];
            }else{
                $list[$key]['status'] = 0;
                $list[$key]['is_exchange'] = 0;
            }
        }
        $this->assign('list',$list);
	    $this->display();
    }

    public function mem_goods_detail(){
	    $id = I('id');
	    if(!$id){
            $this->display('Public:404');
        }
        $db = M('member_goods');
        $member_id = session('USER_KEY_ID');
        $info = $db->where(array('id'=>$id))->find();
        $good_info = M('goods')->where(array('id'=>$info['good_id'] ))->field("is_exchange,status,spec,good_name,logo,price")->find();
        $info['good_name'] = $good_info['good_name'];
        $info['logo'] = $good_info['logo'];
        $info['delivery_time'] = date("m月d日 24:00",strtotime('+3 day', time()));
        /*规格*/
        $spec_arr = $good_info['spec'] ? json_decode($good_info['spec'] ,true):[];

        /*用户地址*/
        $mem_address = M('member_address')->where(array('member_id'=>$member_id))->find();
        $this->assign('info',$info);
        $this->assign('mem_address',$mem_address);
        $this->assign('spec_arr',$spec_arr);
        $this->display();
    }

    public function order_good(){

        if(IS_POST){
            $member_id = session('USER_KEY_ID');

            $valid_num = I('post.valid_num');
            $num = I('post.num');
            $address_id = I('post.address_id');
            $spec = I('post.spec');
            $id = I('post.id');
            $mem_good_info = M("member_goods")->where(array('id'=>$id))->find();

            if($num < 1 ){
                $data['status']= 0;
                $data['info']="兑换数量不能小于1";
                $this->ajaxReturn($data);
            }
            if($num > $mem_good_info['valid_num'] ){
                $data['status']= 0;
                $data['info']="兑换数量超过最大值";
                $this->ajaxReturn($data);
            }
            if(!$address_id){
                $data['status']= 0;
                $data['info']="请填写收货地址";
                $this->ajaxReturn($data);
            }

            /*商品信息*/
            $good_info = M('goods')->where(array('id'=>$mem_good_info['good_id']))->find();

            /*用户地址*/
            $mem_address_info = M('member_address')->where(array('id'=>$address_id))->find();

            $order_data = array(
                'good_id' => $mem_good_info['good_id'],
                'member_id' => $member_id,
                'good_name' => $good_info['good_name'],
                'logo' => $good_info['logo'],
                'spec' => $spec,
                'price' => $good_info['price'],
                'num' => $num,
                'address_id' => $address_id,
                'receipt_name' => $mem_address_info['receipt_name'],
                'receipt_phone' => $mem_address_info['receipt_phone'],
                'receipt_address' => $mem_address_info['receipt_address'],
                'status' => 0,
                'add_time' => time(),
                'currency_id' => $good_info['currency_id']
            );
            $r = M("order")->add($order_data);
            if($r){
                /*减商品卡*/
                M("member_goods")->where(array('id'=>$id))->setDec('valid_num',$num);

                /*发送消息*/
                $msn_data = array(
                    'title' => '兑换商品',
                    'member_id' => $member_id,
                    'type' => 4,
                    'content' => '您兑换的商品<span style="color: #FF0000;">'.$good_info['good_name'].'</span>发货中，请耐心等待',
                    'add_time' => time(),
                    'is_read' => 0,
                );
                M('message')->add($msn_data);
                $data['status']= 1;
                $data['info']="购买成功";
                $this->ajaxReturn($data);
            }else{
                $data['status']= 0;
                $data['info']="购买失败";
                $this->ajaxReturn($data);
            }
        }
    }

    public function index(){

        $member_id = session('USER_KEY_ID');
        $list = M('order')->where(array('member_id'=>$member_id))->order('add_time desc, status asc')->select();

        $this->assign('list',$list);
        $this->display();
    }














}
