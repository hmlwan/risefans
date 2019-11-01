<?php
namespace Mobile\Controller;
use Common\Controller\CommonController;
class WelfareController extends HomeController {
 	public function _initialize(){
 		parent::_initialize();
        /*轮播图*/
        $carousel_list = M('carousel')->where(array('type'=>1,'status'=>1))->select();
        $this->assign('carousel_list',$carousel_list);
        /*商品类型*/
        $good_type_list = M('goods_type')->where(array('status'=>1))->order('sort asc')->select();
        $this->assign('good_type_list',$good_type_list);

 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	/*站外福利*/
	public function outindex(){
        $member_id = $_SESSION['USER_KEY_ID'];
        $model = M('outside_ad');
        $list = $model->where(array('status'=>1))
            ->order("is_recommend desc,update_time desc")
            ->select();
        foreach ($list as &$value){
            $value['tag_json_arr'] = json_decode($value['tag_json'],true);
        }
        $this->assign('list',$list);
        $this->assign('member_id',$member_id);
        $this->display();
     }
     /*站外福利详情*/
     public function outindex_detail(){
         $ad_id = I('ad_id');
         $model = M('outside_ad');
         $ad_info = $model->where(array('id'=>$ad_id))->find();
         /*阅读量+1*/
         $model->where(array('id'=>$ad_id))->setInc('read_num',1);
         $this->assign('ad_info',$ad_info);
         $this->display();
     }
    /*点击量+1*/
    public function add_outindex_click(){
        $id = I('id');
        $model = M('outside_ad');
        if($id){
            $model->where(array('id'=>$id))->setInc('click_num',1);
        }
        $data['status'] = 1;
        $data['info'] = '成功';
        $this->ajaxReturn($data);
    }


     /*商品列表*/
    public function good(){
        $member_id = $_SESSION['USER_KEY_ID'];
        $good_type_id = I('good_type_id');
        $goods_list = M('goods')->where(array('status'=>1,'type_id'=>$good_type_id))
            ->order('sort asc,op_time desc')
            ->select();
        $this->assign('goods_list',$goods_list);
        $this->assign('good_type_id',$good_type_id);
        $this->display();
    }
    /*商品详情*/
    public function good_detail(){
        $member_id = $_SESSION['USER_KEY_ID'];
        $good_id = I('good_id');
        $goods_info = M('goods')->where(array('id'=>$good_id))->find();
        $this->assign('goods_info',$goods_info);
        $this->display();
    }

    /*购买商品卷*/
    public function buy_good(){
        $member_id = $_SESSION['USER_KEY_ID'];
        $goods_db = M('goods');
        $mem_db = D('Member');
        $num = I('num');
        $good_id = I('good_id');
        $member_info = $mem_db->get_info_by_id($member_id);
        /*商品信息*/
        $good_info = $goods_db->where(array('id'=>$good_id))->find();

        /*用户币种*/
        $user_currency_info = M('currency_user')
            ->where(array(
                'member_id'   => $member_id,
                'currency_id' => $good_info['currency_id'])
            )->find();
        $sum_price = $num * $good_info['price'];
        if($sum_price > $user_currency_info['num']){
            $data['status'] = 0;
            $data['info'] = '当前币种数量不足';
            $this->ajaxReturn($data);
        }
        $mem_good_detail_data = array(
            'member_id' => $member_id,
            'type_id' => $good_info['type_id'],
            'type_name' => $good_info['type_name'],
            'good_id' => $good_id,
            'good_name' => $good_info['good_name'],
            'num' => $num,
            'price' => $good_info['price'],
            'is_exchange' => $good_info['is_exchange'],
            'origin_type' => 1,
            'create_time' => time()
        );
        $detail_res = M('member_goods_detail')->add($mem_good_detail_data);

        /*是否存在该商品*/
        $is_exist = M('member_goods')->where(array('member_id'=>$member_id,'good_id'=>$good_id))->find();
        $goods_data = array(
            'member_id' => $member_id,
            'good_id' => $good_id,
            'good_name' => $good_info['good_name'],
            'price' => $good_info['price'],
            'add_time' => time()
        );
        if($is_exist){
            $goods_data['num'] = $is_exist['num'] + $num;
            $goods_data['valid_num'] = $is_exist['valid_num'] + $num;
            $r = M('member_goods')->where(array('id'=>$is_exist['id']))->save($goods_data);
        }else{
            $goods_data['num'] = $num;
            $goods_data['valid_num'] = $num;
            $r =  M('member_goods')->add($goods_data);
        }
        if($r){
            /*扣除币种数量*/
            M('currency_user')->where(array(
                'member_id'   => $member_id,
                'currency_id' => $good_info['currency_id']
            ))->setDec('num',$num);

            $data['status'] = 1;
            $data['info'] = '购买成功';
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['info'] = '购买失败';
            $this->ajaxReturn($data);
        }
    }
}
