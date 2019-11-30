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
		$this->display('Public:face_to_face');
	}
	/*我要兑换*/
	public function buyview(){

        $member_id = session('USER_KEY_ID');
	    /*当前金币*/
        $mem_jb_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');
        $mem_jb_num = number_format($mem_jb_num,2,'.','');

        $this->assign('mem_jb_num',$mem_jb_num);

        /*兑换配置*/
        $ex_conf_list = M("exchange_conf")->where(array('status'=>1))->select();
        foreach ($ex_conf_list as &$value){
            $value['dh_cur_name'] = D('currency')->get_cur_name($value['dh_cur_id']);
            $value['xh_cur_name'] = D('currency')->get_cur_name($value['xh_cur_id']);
            $xh_num = $value['xh_num'];
            if($value['xh_num']>=1000 && $value['xh_num']<10000){
                $xh_num = number_format($value['xh_num']/1000,0,'.','');
                $xh_num = $xh_num.'千';
            }
            if($value['xh_num']>=10000){
                $xh_num = number_format($value['xh_num']/10000,0,'.','');
                $xh_num = $xh_num.'万';
            }
            $value['xh_num_str'] = $xh_num;
            $value['dh_num'] = number_format($value['dh_num'],1,'.','');

        }
        $this->assign('ex_conf_list',$ex_conf_list);
	    $this->display();
    }

    /*兑换莱特币*/
    public function exorder(){
        $member_id = session('USER_KEY_ID');

        $xh_num = I('xh_num');
        $dh_num = I('dh_num');

        $dh_cur_id = I('dh_cur_id');
        $xh_cur_id = I('xh_cur_id');

        $dh_cur_name = D('currency')->get_cur_name($dh_cur_id);
        $xh_cur_name = D('currency')->get_cur_name($xh_cur_id);
        if(!$dh_num){
            $data['status']= 0;
            $data['info']="请选择兑换项";
            $this->ajaxReturn($data);
        }
        $mem_jb_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>$xh_cur_id))->getField('num');
        if($mem_jb_num<$xh_num){
            $data['status'] = 0;
            $data['info'] = $xh_cur_name."不足";
            $this->ajaxReturn($data);
        }
        /*减金币*/
        $cur_db = D("currency");
        $dec_r = $cur_db->mem_dec_cur($xh_cur_id,$xh_num);
        if(false !== $dec_r){
            /*加莱特币*/
            $inc_r = $cur_db->mem_inc_cur($dh_cur_id,$dh_num);
            if(false === $inc_r){
                $data['status']= 0;
                $data['info']= $dh_cur_name."兑换失败";
                $this->ajaxReturn($data);
            }
            /*兑换记录*/
            M("exchange_order")->add(array(
                'member_id' => $member_id,
                'dh_cur_id' => $dh_cur_id, //ltb
                'dh_num' => $dh_num,
                'xh_cur_id' => $xh_cur_id, //jb
                'xh_num' => $xh_num,
                'add_time' => time(),
            ));
            /*统计*/
            $inc_balance = D('currency')->mem_cur_num($dh_cur_id,$member_id);
            M('trade')->add(
                array(
                    'member_id' => $member_id,
                    'currency_id' => $dh_cur_id,
                    'num' => $dh_num,
                    'content' => "{$xh_cur_name}兑换{$dh_cur_name}，增加{$dh_cur_name}".$dh_num,
                    'type' => 1,
                    'trade_type' => 12,
                    'add_time' => time(),
                    'balance' => $inc_balance,
                    'oldbalance' => $inc_balance +$dh_num,
                )
            );
            $dec_balance = D('currency')->mem_cur_num($xh_cur_id,$member_id);
            M('trade')->add(
                array(
                    'member_id' => $member_id,
                    'currency_id' => $xh_cur_id,
                    'num' => $xh_num,
                    'content' => "{$xh_cur_name}兑换{$dh_cur_name}，消耗{$xh_cur_name}".$xh_num,
                    'type' => 2,
                    'trade_type' => 12,
                    'add_time' => time(),
                    'balance' => $dec_balance,
                    'oldbalance' => $dec_balance -$xh_num,
                )
            );
            $data['status']= 1;
            $data['info']="兑换".$dh_num."{$dh_cur_name}成功";
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

        foreach ($list as &$value){
            $value['dh_cur_name'] = D('currency')->get_cur_name($value['dh_cur_id']);
            $value['xh_cur_name'] = D('currency')->get_cur_name($value['xh_cur_id']);
//            $key = date("Y-m-d",$value['add_time']);
//            $arr[$key][] = $value;
        }

        $this->assign('list',$list);
        $this->display();

    }
}
