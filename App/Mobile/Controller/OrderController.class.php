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
	/*充值*/
	public function recharge(){
        $member_id = session('USER_KEY_ID');

        $db = M('rechage_record');
        $data = array(
            'member_id' => $member_id,
            'recharge_no' => "LVSDKksdskdjsn98VSDK79dsdsVSDKlkjfs",
            'add_time' => time(),
            'num' => 2.852558258,
            'currency_id' => 2,
            'status' => 0,
        );
        $db->add($data);
	    $this->display();
    }
    /*充值记录*/
    public function recharge_record(){
        $db = M('rechage_record');
        $member_id = session('USER_KEY_ID');
        $list = $db->where(array('member_id'=>$member_id))->order('id desc')->select();
        foreach ($list as &$value){
            $value['currency_en'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_en');
        }
        $this->assign('list',$list);
        $this->display();
    }
    /*提现*/
    public function withdraw(){
        $member_id = session('USER_KEY_ID');
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>2))->getField('num');
        /*莱特币最小提现数量*/
        $ltc_min_num = $this->config['ltc_min_num'];
        /*莱特币提现手续费*/
        $ltc_service_charge_rate = $this->config['ltc_service_charge_rate'];
        /*手续费*/
        $ltc_service_charge_min = ($ltc_min_num/1) * $ltc_service_charge_rate;
        $ltc_service_charge_max = ($mem_cur_num/1) * $ltc_service_charge_rate;

        if($mem_cur_num < $ltc_service_charge_min){
            $ltc_service_charge_max = $ltc_service_charge_min;
        }
        $ltc_service_charge_min = number_format($ltc_service_charge_min,8,'.','');
        $ltc_service_charge_max = number_format($ltc_service_charge_max,8,'.','');
        $this->assign('mem_cur_num',$mem_cur_num);
        $this->assign('ltc_min_num',$ltc_min_num);
        $this->assign('ltc_service_charge_min',$ltc_service_charge_min);
        $this->assign('ltc_service_charge_max',$ltc_service_charge_max);
        $this->assign('ltc_service_charge_rate',$ltc_service_charge_rate);

        $this->display();
    }
    /*提现操作*/
    public function  withdraw_order(){
        $member_id = session('USER_KEY_ID');
        $yzm = I('yzm');
        $cur_address = I('cur_address');
        $total_num = I('total_num');
        $service_charge = I('service_charge');
        if(!$yzm){
            $data['status']= 0;
            $data['info']="请输入验证码";
            $this->ajaxReturn($data);
        }
        if(!$cur_address){
            $data['status']= 0;
            $data['info']="请输入提币地址";
            $this->ajaxReturn($data);
        }
        if(!$total_num){
            $data['status']= 0;
            $data['info']="请输入提币数量";
            $this->ajaxReturn($data);
        }
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>2))->getField('num');
        if($mem_cur_num < $total_num){
            $data['status']= 0;
            $data['info']="提币数量不足";
            $this->ajaxReturn($data);
        }
        $db = M('withdraw_record');
        $save_data = array(
            'member_id' => $member_id,
            'cur_address' => $cur_address,
            'currency_id' => 2,
            'num' => $total_num - $service_charge,
            'status' =>0,
            'service_charge' =>$service_charge,
            'total_num' =>$total_num,
            'add_time' =>time(),
        );
        $r = $db->add($save_data);
        if(false !== $r){
            $data['status']= 1;
            $data['info']="提币成功，请等待";
            $this->ajaxReturn($data);
        }else{
            $data['status']= 0;
            $data['info']="提币失败";
            $this->ajaxReturn($data);
        }
    }


    /*提现记录*/
    public function withdraw_record(){
        $db = M('withdraw_record');
        $member_id = session('USER_KEY_ID');
        $list = $db->where(array('member_id'=>$member_id))->order("id desc")->select();
        $this->assign('list',$list);

        $this->display();
    }

    public function test(){
        $this->display();

    }










}
