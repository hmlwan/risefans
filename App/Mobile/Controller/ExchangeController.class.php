<?php
namespace Mobile\Controller;
use Common\Controller\CommonController;
class ExchangeController extends HomeController {
 	public function _initialize(){
 		parent::_initialize();
 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	/*我要买*/
	public function buyview(){

        $data = $this->get_exchange_pub(2);
        $this->assign('list',$data['list']);
        $this->assign('limit',json_encode($data['limit']));
        $this->assign('unit_price',$data['unit_price']);
        $this->assign('num_json',$data['num_json']);

	    $this->display();
    }
    /*我要卖*/
    public function saleview(){
        $data = $this->get_exchange_pub(1);
        $this->assign('list',$data['list']);
        $this->assign('limit',json_encode($data['limit']));
        $this->assign('unit_price',$data['unit_price']);
        $this->assign('num_json',$data['num_json']);
        $this->display();
    }

    /*获得兑换发布数据*/
    public function get_exchange_pub($type){

        $where = array(
            'status' => 1,
            'type' => $type
        );
        /*失效时间*/
        $time = time();
        if($this->ex_config['invalid_time'] > 0){
            $time = $time - $this->ex_config['invalid_time'] * 3600;
            $where['add_time'] = array('EGT' ,$time);
        }

        $list = M('exchange_pub')->where($where)->order("add_time desc")->select();

        $m_volum_db = M("member_exchange_volume");
        if($list){
            foreach ($list as &$value){
                $volum_num = $m_volum_db
                    ->where(array(
                        'member_id'=>$value['member_id'],
                        'type'=>$type,
                        'currency_id'=>$value['currency_id'])
                    )
                    ->find();
                $total_num = 0;
                $single_order_num = 0;
                if($volum_num){
                    $total_num = $volum_num['total_num'];
                    $single_order_num = $volum_num['single_order_num'];
                }
                $value['total_num'] = $total_num;
                $value['single_order_num'] = $single_order_num;
                $value['nick_name'] = M('member_info')->where(array('member_id'=>$value['member_id']))->getField('nick_name');
                $value['currency_name'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_name');
            }
        }
        /*交易时间*/
        $mtime = strtotime(date("H:i:s",time()));
        $limit = array(
            'is_open_deal' => $this->ex_config['is_open_deal']
        );
        if($mtime >= strtotime($this->ex_config['deal_start_time']) && $mtime <= strtotime($this->ex_config['deal_end_time'])){
            $limit['is_online'] = 1;
        }else{
            $limit['is_online'] = 0;
            $limit['deal_start_time'] = $this->ex_config['deal_start_time'];
            $limit['deal_end_time'] = $this->ex_config['deal_end_time'];
        }
        $data['list'] =  $list;
        $data['limit'] =  $limit;
        /*数量限额*/
        $num_json = $this->ex_config['num_json'];
        $num_json = json_decode($num_json,true);
        /*单价*/
        $unit_price = $this->ex_config['unit_price'];

        $data['unit_price'] =  number_format($unit_price,2,'.','');
        $data['num_json'] =  $num_json;
        return $data;
    }

    /*发布*/
    public function ex_pub(){
        $member_id = $_SESSION['USER_KEY_ID'];
        $USER_KEY = $_SESSION['USER_KEY'];
        $type = I('type');
        $num = I('num');

        $unit_price = I('unit_price');
        if($num < 0){
            $data['status'] = 0;
            $data['info'] = '请选择数量';
            $this->ajaxReturn($data);
        }
        /*查询是否冻结*/
        $is_freeze = M("exchange_freeze")->where(array('member_id'=>$member_id))->find();
        if($is_freeze){
            $data['status'] = 0;
            $data['info'] = '您已被冻结，暂时不能挂单';
            $this->ajaxReturn($data);
        }
        /*查询未成交委托单数量*/
         $tickets = M("exchange_pub")
             ->where(array('member_id'=>$member_id,'type'=>$type,'status'=>1))
             ->count();
         $limit_order_ticket_nums = $this->ex_config['limit_order_ticket_nums'];
        if($limit_order_ticket_nums >0 ){
            if($tickets > $limit_order_ticket_nums){
                $data['status'] = 0;
                $data['info'] = '最多可委托'.$limit_order_ticket_nums.'单';
                $this->ajaxReturn($data);
            }
        }

        $sum_price = $unit_price * $num;
        /*挂卖单 判断币数量*/
        if($type == 2){
            $currency_info = M('currency_user')
                ->where(array('member_id'=>$member_id,'currency_id'=>$this->ex_config['currency_id']))
                ->find();
            if($currency_info['num'] < $sum_price){
                $data['status'] = 0;
                $data['info'] = '币种数量不足';
                $this->ajaxReturn($data);
            }
        }
        $db = M('exchange_pub');
        $order_no = '111'.time().'001';
        if($db->where(array('order_no'=>$order_no))->find()){
            $order_no = '111'.time().'002';
        }
        $data = array(
            'order_no' => $order_no,
            'type' => $type,
            'member_id' => $member_id,
            'phone' => $USER_KEY,
            'currency_id' => $this->ex_config['currency_id'],
            'price' => $unit_price,
            'num' => $num,
            'sum_price' => $sum_price,
            'status' => 1,
            'add_time' => time()
        );
        $mem_info = D("Member")->get_info_by_id($member_id);
        if($type == 2){
            $data['zfb_no'] = $mem_info['zfb_no'];
            $data['zfb_username'] = $mem_info['true_name'];
        }


        $r = $db->add($data);
        if($r){
            if($type == 2){
                M('currency_user')
                    ->where(array('member_id'=>$member_id,'currency_id'=>$this->ex_config['currency_id']))
                    ->setDec('num',$sum_price);
            }
            $data['status'] = 1;
            $data['info'] = '挂单成功';
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['info'] = '挂单失败';
            $this->ajaxReturn($data);
        }

    }
    /*委托单*/
    public function orderticket(){

        $member_id = $_SESSION['USER_KEY_ID'];
        $db = M('exchange_pub');

        $list = $db
            ->where(array('member_id'=>$member_id,'status'=>1))
            ->order("add_time desc")
            ->select();

        $this->assign('list',$list);
        $this->display();
    }
    /*撤销*/
    public  function pub_cancel(){
        $member_id = $_SESSION['USER_KEY_ID'];
        $db = M('exchange_pub');

        $id = I('id');
        $type = I('type');
        if(!$id){
            $data['status'] = 0;
            $data['info'] = '未知错误';
            $this->ajaxReturn($data);
        }
        $info = $db->where(array('id'=>$id))->find();
        $r = $db->where(array('id'=>$id))->save(array('status'=>3));
        if($r){
            /*返还*/
            if($type == 2){
                M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>$info['currency_id']))->setInc('num',$info['num']);
            }
            $data['status'] = 1;
            $data['info'] = '撤销成功';
            $this->ajaxReturn($data);
        }else{
            $data['status'] = 0;
            $data['info'] = '撤销失败';
            $this->ajaxReturn($data);
        }
    }
    /*交易*/
    public function deal_ex(){
        $member_id = $_SESSION['USER_KEY_ID'];
        $type = I('type');
        $id = I('id');
        if(!$id){
            $data['status'] = 0;
            $data['info'] = '未知错误';
            $this->ajaxReturn($data);
        }
        $ex_pub_db = M('exchange_pub');

        $pub_info = $ex_pub_db->where(array('id'=>$id))->find();
        $mem_db = D("Member");
        if($type == 1){
            $msg = "购买";
            $buy_mem_id = $member_id;
            $sale_mem_id = $pub_info['member_id'];
        }else{
            $msg = "出售";
            $buy_mem_id = $pub_info['member_id'];
            $sale_mem_id = $member_id;
        }
        $buy_mem_info = $mem_db->get_info_by_id($buy_mem_id);
        $sale_mem_info = $mem_db->get_info_by_id($sale_mem_id);

        if($pub_info['member_id'] == $member_id){
            $data['status'] = 0;
            $data['info'] = '不能'.$msg.'自己发布的挂单';
            $this->ajaxReturn($data);
        }
        $ex_order_db = M('exchange_order');

        $order_no = '111'.time().'001';
        if($ex_order_db->where(array('order_no'=>$order_no))->find()){
            $order_no = '111'.time().'002';
        }
        $save_data = array(
            'pub_id' => $pub_info['id'],
            'order_no' => $order_no,
            'type' => $type,
            'buy_mem_id' => $buy_mem_id,
            'buy_mem_phone' => $buy_mem_info['phone'],
            'sale_mem_id' =>$sale_mem_id,
            'sale_mem_phone' => $sale_mem_info['phone'],
            'currency_id' => $pub_info['currency_id'],
            'zfb_no' => $sale_mem_info['zfb_no'],
            'zfb_username' => $sale_mem_info['true_name'],
            'price' => $pub_info['price'],
            'num' => $pub_info['num'],
            'status' => 1,
            'add_time' => time(),
            'sum_price' => $pub_info['price'] *  $pub_info['num']
        );
        $res = $ex_order_db->add($save_data);
        if(!$res ){
            $data['status'] = 0;
            $data['info'] = '下单失败';
            $this->ajaxReturn($data);
        }else{
            $ex_pub_db->where(array('id'=>$id))->save(array('status'=>2));
            $data['status'] = 1;
            $data['info'] = '下单成功';
            $data['url'] = U('Exchange/orderview');
            $this->ajaxReturn($data);
        }
    }

    /*订单*/
    public function orderview(){
        $ex_order_db = M('exchange_order');
        $member_id = $_SESSION['USER_KEY_ID'];
        $where['buy_mem_id'] = $member_id;
        $where['sale_mem_id'] = $member_id;
        $where['_logic'] = "OR";

        $list = $ex_order_db
            ->where($where)
            ->order("status asc,add_time desc")
            ->select();

        foreach ($list as &$value){

            if($value['buy_mem_id'] == $member_id){
                $value['type'] =  1;
                $show_name_id = $value['sale_mem_id'];
            }else if($value['sale_mem_id'] == $member_id){
                $value['type'] = 2;
                $show_name_id = $value['buy_mem_id'];
            }
            $show_name = M("member_info")->where(array('member_id'=>$show_name_id))->getField('nick_name');
            $value['show_name'] = $show_name;
            $value['currency_name'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_name');
        }
        $this->assign('list',$list);
        $this->display();
    }
    /*购买云链*/
    public function buy_detail(){
        $id = I('id');
        $member_id = $_SESSION['USER_KEY_ID'];

        if(!$id){
            $this->display('Public:404');
        }
        $db = M('exchange_pub');
        $pub_info = $db->where(array('id'=>$id))->find();

        $pub_mem_id = $pub_info['member_id'];
        $pub_mem = D("Member")->get_info_by_id($pub_mem_id);

        /*单数*/
        $volume = M('member_exchange_volume')
            ->where(array(
                'member_id' => $pub_mem_id,
                'type'=> $pub_info['type'],
                'currency_id' => $pub_info['currency_id']
                )
            )
            ->find();
        $pub_mem["total_num"] = $volume['total_num'] ? $volume['total_num'] :0;
        $pub_mem["single_order_num"] = $volume['single_order_num']?$volume['single_order_num']:0;
        $pub_mem["currency_name"] = M('currency')->where(array('id'=>$pub_info['currency_id']))->getField('currency_name');

        /*出售*/
        if($pub_info['type'] == 1){
            $m_info =  D("Member")->get_info_by_id($member_id);
            $pub_info['zfb_no'] = $m_info['zfb_no'];
            $pub_info['true_name'] = $m_info['true_name'];
        }

        $this->assign('pub_info',$pub_info);
        $this->assign('pub_mem',$pub_mem);
        $this->display();
    }
    /*付款订单*/
    public function pay_order(){
        $order_id = I('order_id');
        $ex_order_db = M('exchange_order');
        $info = $ex_order_db->where(array('id'=>$order_id))->find();
        $member_id = $_SESSION['USER_KEY_ID'];
        $info['otype']  = $info['type'] ;
        if($info['buy_mem_id'] == $member_id){
            $info['type'] =  1;

        }else if($info['sale_mem_id'] == $member_id){
            $info['type'] = 2;
        }
        $info['currency_name'] = M("currency")->where(array('currency_id'=>$info['currency_id']))
            ->getField('currency_name');
        $interval_times = 0;
        /*判断状态*/
        /*未付款，付款倒计时*/
        if($info['status'] == 1){
            $interval_times = time()- $info['add_time'];
            if($interval_times > 60*60){
                $interval_times = 0;
            }else{
                $interval_times = 60*60 - $interval_times;
            }
        }

        /*已付款，确认倒计时*/
        if($info['status'] == 2){
            $interval_times = time()-$info['pay_time1'];
            if($interval_times > 12*60*60){
                $interval_times = 0;
            }else{
                $interval_times = 12*60*60 - $interval_times;
            }
        }

        /*投诉，倒计时*/
        if($info['status'] == 5){
            $interval_times = time()-$info['dispute_time'];
            if($interval_times > 12*60*60){
                $interval_times = 0;
            }else{
                $interval_times = 12*60*60 - $interval_times;
            }
        }
        $this->assign('info',$info);
        $this->assign('interval_times',$interval_times);
        /*确认领取*/
        if($info['status'] == 3){
            $this->redirect("pay_complete?order_id=$order_id");
        }
        $this->display();
    }

    /*确认操作*/
    public function receive_confirm(){
        $id = I('id');
        $type = I('type');
        $otype = I('otype');
        $status= I('status');
        $SysSecond = I('SysSecond');

        $member_id = $_SESSION['USER_KEY_ID'];
        $ex_order_db = M('exchange_order');

        $ex_order_info = $ex_order_db->where(array('id'=>$id))->find();

        $flag = '';
        if($type == 1){ /*购买*/
            if($otype == 1){
                switch ($status){
                    case 1:
                        $flag = 1; /*我已向对方付款*/
                        $msg = "我已向对方付款";
                        break;
                    case 2:
                        $flag = 2;  /*等待对方确认*/
                        $msg = "等待对方确认";
                        break;
                }
            }elseif ($otype == 2){
                switch ($status){
                    case 1:
                        $flag = 3; /*等待对方出售*/
                        $msg = "等待对方出售";
                        break;
                    case 2:
                        $flag = 4;  /*我已向对方付款*/
                        $msg = "我已向对方付款";
                        break;
                }
            }
        }elseif ($type == 2){ /*出售*/
            if($otype == 1){
                switch ($status){
                    case 1:
                        $flag = 5; /*等待对方付款*/
                        $msg = "等待对方付款";

                        break;
                    case 2:
                        $flag = 6;  /*我已收到款*/
                        $msg = "我已收到款";

                        break;
                }
            }elseif ($otype == 2){
                switch ($status){
                    case 1:
                        $flag = 7; /*我已出售*/
                        $msg = "我已出售";

                        break;
                    case 2:
                        $flag = 8;  /*等待对方付款*/
                        $msg = "等待对方付款";
                        break;
                }
            }
        }
        if($flag == 3 || $flag == 5 || $flag == 2 || $flag == 8){
            if($SysSecond > 0){
                $data['status'] = 2;
                $data['info'] = $msg;
                $this->ajaxReturn($data);
            }else{
                $data['status'] = 0;
                $data['info'] = "请联系对方确认";
                $this->ajaxReturn($data);
            }
        }

        if($flag == 1 || $flag == 7 ){
            $res = $ex_order_db->where(array('id'=>$id))->save(array('pay_time1'=>time(),'status'=>2));
            if($res){
                $data['status'] = 3;
                $data['info'] = $msg;
                $data['url'] =  U("Exchange/pay_order",array('order_id'=>$id));
                $this->ajaxReturn($data);
            }else{
                $data['status'] = 0;
                $data['info'] = "操作失败";
                $this->ajaxReturn($data);
            }
        }
        if($flag == 4 || $flag == 6 ){
            $res = $ex_order_db->where(array('id'=>$id))->save(array('pay_time2'=>time(),'status'=>3));
            if($res){
                $data['status'] = 3;
                $data['info'] = $msg;
                $data['url'] = U("Exchange/pay_complete",array('order_id'=>$id));
                $this->ajaxReturn($data);
            }else{
                $data['status'] = 0;
                $data['info'] = "操作失败";
                $this->ajaxReturn($data);
            }
        }
    }


    /*交易完成*/
    public function pay_complete(){
        $order_id = I('order_id');
        $ex_order_db = M('exchange_order');
        $info = $ex_order_db->where(array('id'=>$order_id))->find();
        $info['buy_mem_name'] = M("member_info")->where(array('member_id'=>$info['buy_mem_id']))->getField('nick_name');
        $info['sale_mem_name'] = M("member_info")->where(array('member_id'=>$info['sale_mem_id']))->getField('nick_name');
        $member_id = $_SESSION['USER_KEY_ID'];
        $info['otype']  = $info['type'] ;
        if($info['buy_mem_id'] == $member_id){
            $info['type'] =  1;

        }else if($info['sale_mem_id'] == $member_id){
            $info['type'] = 2;
        }
        $info['currency_name'] = M("currency")->where(array('currency_id'=>$info['currency_id']))
            ->getField('currency_name');

        $this->assign('info',$info);
        $this->display();
    }
    /*领取云币*/
    public function receive_currency(){
        $ex_order_db = M('exchange_order');
        $order_id = I('id');
        $info = $ex_order_db->where(array('id'=>$order_id))->find();
        $member_id = $_SESSION['USER_KEY_ID'];

        $type = I('type');
        if($type == 1){
            $is_exist = M('currency_user')
                ->where(array('member_id'=>$member_id,'currency_id'=>$info['currency_id']))
                ->find();
            if($is_exist){
                $res = M("currency_user")->where(array('cu_id'=>$is_exist['cu_id']))->setInc('num',$info['num']);
            }else{
                $res = M("currency_user")->add(array(
                    'member_id' => $member_id,
                    'currency_id' => $info['currency_id'],
                    'num' => $info['num'],
                    'status' => 1,
                ));
            }
            if($res){
                $ex_order_db->where(array('id'=>$order_id))->save(array('complete_time'=>time(),'status'=>4));
                /*交易流水记录*/
                $trade_db = M('trade');
                $buy_trade_data = array(
                    'currency_id' => $info['currency_id'],
                    'trade_type' =>3,
                    'add_time' => time(),
                    'type' => 1,
                    'member_id' => $info['buy_mem_id']
                );
                $trade_db->add($buy_trade_data);
                $sale_trade_data = array(
                    'currency_id' => $info['currency_id'],
                    'trade_type' =>3,
                    'add_time' => time(),
                    'type' => 2,
                    'member_id' => $info['sale_mem_id']
                );
                $trade_db->add($sale_trade_data);

                M('exchange_pub')->where(array('id'=>$info['pub_id']))->save(array('status'=>4));

                $data['status'] = 1;
                $data['info'] = "领取成功";
                $data['url'] = U("Exchange/pay_complete",array('order_id'=>$order_id));
                $this->ajaxReturn($data);
            }else{
                $data['status'] = 0;
                $data['info'] = "领取失败";
                $this->ajaxReturn($data);
            }
        }
    }

    /*直接买投诉*/
    public function buy_complain(){
        $this->display();
    }
    /*直接卖申诉*/
    public function sale_appeal(){
        $ex_order_db = M('exchange_order');
        $order_id = I('order_id');
        $info = $ex_order_db->where(array('id'=>$order_id))->find();

        $member_id = $_SESSION['USER_KEY_ID'];
        $info['otype']  = $info['type'] ;
        if($info['buy_mem_id'] == $member_id){
            $info['type'] =  1;

        }else if($info['sale_mem_id'] == $member_id){
            $info['type'] = 2;
        }
        /*已投诉*/
        if($info['status'] = 5){
            $dispute_db = M('exchange_order_dispute_result');
            $dispute_info = $dispute_db->where(array('exchange_order_id'=>$order_id))->find();
        }
        $this->assign('dispute_info',$dispute_info);
        $this->assign('info',$info);
        $this->display();
    }
    /*投诉操作*/
    public function appeal_op(){
        $ex_order_db = M('exchange_order');
        $order_id = I('order_id');
        $dispute_db = M('exchange_order_dispute_result');

    }

}
