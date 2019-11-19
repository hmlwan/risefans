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
	/*我要兑换*/
	public function buyview(){

        $member_id = session('USER_KEY_ID');
	    /*当前金币*/
        $mem_jb_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');
        $mem_jb_num = number_format($mem_jb_num,0,'','');

        $this->assign('mem_jb_num',$mem_jb_num);

	    $this->display();
    }

    /*兑换莱特币*/
    public function exorder(){
        $member_id = session('USER_KEY_ID');

        $xh_num = I('xh_num');
        $dh_num = I('dh_num');
        if(!$dh_num){
            $data['status']= 0;
            $data['info']="请选择兑换项";
            $this->ajaxReturn($data);
        }
        $mem_jb_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');
        if($mem_jb_num<$xh_num){
            $data['status'] = 0;
            $data['info'] = "金币不足";
            $this->ajaxReturn($data);
        }
        /*减金币*/
        $cur_db = D("currency");
        $dec_r = $cur_db->mem_dec_cur(3,$xh_num);
        if(false !== $dec_r){
            /*加莱特币*/
            $inc_r = $cur_db->mem_inc_cur(2,$dh_num);
            if(false === $inc_r){
                $data['status']= 0;
                $data['info']="兑换失败";
                $this->ajaxReturn($data);
            }
            /*兑换记录*/
            M("exchange_order")->add(array(
                'member_id' => $member_id,
                'dh_cur_id' => 2, //ltb
                'dh_num' => $dh_num,
                'xh_cur_id' => 3, //jb
                'xh_num' => $xh_num,
                'add_time`' => time(),
            ));
            /*统计*/
            M('trade')->add(
                array(
                    'member_id' => $member_id,
                    'currency_id' => 2,
                    'num' => $dh_num,
                    'content' => "金币兑换莱特币，增加莱特币".$dh_num,
                    'type' => 1,
                    'trade_type' => 12,
                    'add_time' => time(),
                )
            );
            M('trade')->add(
                array(
                    'member_id' => $member_id,
                    'currency_id' => 3,
                    'num' => $xh_num,
                    'content' => "金币兑换莱特币，消耗金币".$xh_num,
                    'type' => 2,
                    'trade_type' => 12,
                    'add_time' => time(),
                )
            );
            $data['status']= 1;
            $data['info']="兑换".$dh_num."莱特币成功";
            $this->ajaxReturn($data);
        }else{
            $data['status']= 0;
            $data['info']="兑换失败";
            $this->ajaxReturn($data);
        }
    }
    /*兑换记录*/
    public function exrecord(){
        $member_id = session('USER_KEY_ID');

        $db = M('exchange_order');
        $list = $db->where(array('member_id'=>$member_id))->order('add_time desc')->select();
        $arr = array();
        foreach ($list as $k=>$value){
            $key = date("Y-m-d",$value['add_time']);
            $arr[$key][] = $value;
        }
        $this->assign('list',$arr);
        $this->display();

    }
}
