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
		$this->display('Public:face_to_face');
	}
	/*莱特币充值*/
	public function recharge(){
        $member_id = session('USER_KEY_ID');
        $db = D("Currency");
        $currency_id = 2;
        $currency_info = $db->get_cur_info($currency_id);
        if(!$currency_info || $currency_info['is_lock'] == 1){
            header("无效币种请联系管理员");
            $this->display('Public:face_to_face');
        }
        $list = $db->mem_cur($currency_id,$member_id);
        //设置充值地址
        if(empty($list['chongzhi_url'])){
            $address=$this->qianbao_new_address($currency_info);
            $this->setCurrentyMemberByMemberId($_SESSION['USER_KEY_ID'], $currency_id, 'chongzhi_url', $address);
            $list['chongzhi_url']=$address;
        }
        $this->assign("currency",$currency_info);//货币信息

        $this->assign("list",$list);
	    $this->display();
    }
    /*充值记录*/
    public function recharge_record(){
        $db = M('tibi');
        $member_id = session('USER_KEY_ID');
        $list = $db->where(array(
            'user_id'=>$member_id,
            'status'=>array('in',array(2,3,5))
        ))
         ->order('id desc')->select();
        foreach ($list as &$value){
            $value['currency_en'] = M('currency')->where(array('currency_id'=>$value['currency_id']))->getField('currency_en');
        }
        $this->assign('list',$list);
        $this->display();
    }

    /**
     * 充值方法
     * @return boolean
     */

    public function chongzhi_function(){
        //     	$where['status']=array("in",array(3));//1与3分别为 提币成功 与充值成功;
        //     	$where['user_id']=$_SESSION['USER_KEY_ID'];
        //     	$count = M("Tibi")->where($where)->count();
        $id=I("currency_id");//货币id；
        if(empty($id)){
            return false;
        }
        $currency=M("Currency")->where("currency_id='$id'")->find();//这个是货币
        if(empty($currency)){
            return false;
        }
        $list=$this->trade_qianbao($_SESSION['USER_KEY'],$currency);
        foreach ($list as $k=>$v){
            $data["currency_id"]=$currency['currency_id'];//货币id写入
            if($v['category']=='receive'){
                $data[]=array();
                $data['user_id']=$_SESSION['USER_KEY_ID'];
                $data['url']=$v['address'];//地址
                $data['name']=$v['account'];//标签
                $data['add_time']=$v['time'];//时间
                $data['num']=$v['amount'];//数量
                $tibi_txid=M("Tibi")->where("ti_id='{$v['txid']}'")->find();
                if(!empty($tibi_txid)){
                    //如果已经存在  而且是已经完成状态 不处理直接跳出循环
                    if($tibi_txid['status']==3){
                        continue;
                    }
                    if(!empty($v['confirmations'])){
                        $data['status']=3;//3表示充值完成
                        $data['check_time']=$v['timereceived'];//确认时间
                        $re=M("Tibi")->where("ti_id='{$v['txid']}'")->save($data);//修改状态 表示已经完成
                        M("currency_user")->where("member_id='{$_SESSION['USER_KEY_ID']}' and currency_id='$id'")->setInc("num",$v['amount']);//给User表加钱
                    }
                }else{
                    $data['ti_id']=$v['txid'];//写入交易id号
                    if(!empty($v['confirmations'])){
                        $data['status']=3;//3表示充值完成
                        $data['check_time']=$v['timereceived'];//确认时间
                        $re=M("Tibi")->add($data);//修改状态 表示已经完成
                        M("currency_user")->where("member_id='{$_SESSION['USER_KEY_ID']}'  and currency_id='$id' ")->setInc("num",$v['amount']);//给User表加钱
                    }else{
                        $data['status']=2;//2表示充值中
                        $re=M("Tibi")->add($data);
                    }
                }
            }
        }
        if($re){
            $arr['status']=1;
            $this->ajaxReturn($arr);
        }
    }
    /*提现*/
    public function withdraw(){
        $member_id = session('USER_KEY_ID');
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>2))->getField('num');
        /*莱特币最小提现数量*/
        $ltc_min_num = $this->config['ltc_min_num'];
        /*莱特币提现手续费*/
        $vip_level_db = M("vip_level_config");
        $vip_level = D("member")->get_vip_level($member_id);
        $ltc_service_charge_rate = $vip_level_db->where(array('type'=>$vip_level))->getField('ltc_service_charge_rate');
        $ltc_service_charge_rate = $ltc_service_charge_rate?$ltc_service_charge_rate:0;
        $ltc_service_charge_rate = number_format($ltc_service_charge_rate,8,'.','');
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
//        $this->assign('ltc_service_charge_min',$ltc_service_charge_min);
//        $this->assign('ltc_service_charge_max',$ltc_service_charge_max);
        $this->assign('ltc_service_charge_rate',$ltc_service_charge_rate);
        $phone = M('member')->where(array('member_id'=>$member_id))->getField('phone');
        $this->assign('phone',$phone);
        $this->display();
    }
    /*提现操作*/
    public function  withdraw_order(){
        $member_id = session('USER_KEY_ID');
        $yzm = I('yzm');
        $cur_address = I('cur_address');
        $total_num = I('total_num');
        $service_charge = I('service_charge');
        $phone = I('phone');
        $ltc_min_num = $this->config['ltc_min_num'];
        $currency_id = 2;
        if(!$cur_address){
            $data['status']= 0;
            $data['info']="请输入提币地址";
            $this->ajaxReturn($data);
        }
        $currency=M("Currency")->where("currency_id='$currency_id'")->find();//这个是货币
        if(empty($currency)){
            $arr['status']=0;
            $arr['info']="无效货币，无法提币";
            $this->ajaxReturn($arr);exit;
        }
        if(!$total_num){
            $data['status']= 0;
            $data['info']="请输入提币数量";
            $this->ajaxReturn($data);
        }
        if(!$yzm){
            $data['status']= 0;
            $data['info']="请输入验证码";
            $this->ajaxReturn($data);
        }
        if($yzm != $_SESSION['code']){
            $data['status']= 0;
            $data['info']="验证码不正确";
            $this->ajaxReturn($data);
        }
        if($total_num < 0.0001){
            $data['status']= 0;
            $data['info']="请填写大于0.0001的数量";
            $this->ajaxReturn($data);
        }
        if($total_num<$ltc_min_num){
            $data['status']= 0;
            $data['info']="请填写大于{$ltc_min_num}的数量";
            $this->ajaxReturn($data);
        }
        if($total_num > $currency['currency_all_tibi']){
            $arr['status'] = 0;
            $arr['info']="已超出最大限制";
            $this->ajaxReturn($arr);exit;
        }
        /*一天只能提币一次*/
        $exist_td = M('tibi')->where(array(
            'user_id' => $member_id,
            'status' => array('in',array(0,1,4)),
            'add_time' => array('between',array(
                strtotime(date("Y-m-d 0:0:0"),time()),strtotime(date("Y-m-d 23:59:59"),time())
            ))
        ))->find();
        if($exist_td){
            $arr['status'] = 0;
            $arr['info']="每日只能提现一次";
            $this->ajaxReturn($arr);exit;
        }

        /*添加钱包*/
        //检测地址是否已经存在
        $where = array(
            'qianbao_url' => $cur_address,
            'user_id' => array('neq',$member_id),
            'currency_id' => $currency_id,
        );
        $re= M("Qianbao_address")->where($where)->find();
        if(!empty($re)){
            $arr['status']=0;
            $arr['info']="此地址已经绑定，请核实真实地址";
            $this->ajaxReturn($arr);exit;
        }
        $qb_data = array();
        $qb_data['currency_id']=$currency_id;//货币id
        $qb_data['name']=$phone;
        $qb_data['qianbao_url']=$cur_address;
        $qb_data['add_time']=time();
        $qb_data['user_id']=$member_id;
        $qb_data['status']=1;

        //判断看这个钱包地址是否是真实地址
        if(!$this->check_qianbao_address($cur_address,$currency)){
            $arr['status']=0;
            $arr['info']="提币地址不是一个有效地址";
            $this->ajaxReturn($arr);exit;
        }
        if(empty($uq)){
            $qa=M("Qianbao_address")->add($qb_data);
        }else{
            $qa=M("Qianbao_address")->where("id='{$uq['id']}'")->save($qb_data);
        }
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>2))->getField('num');
        if($mem_cur_num < $total_num){
            $data['status']= 0;
            $data['info']="提币数量不足";
            $this->ajaxReturn($data);
        }
        $actual = $total_num-$service_charge;
        if($actual<= 0){
            $data['status']= 0;
            $data['info']="提币数量少于手续费";
            $this->ajaxReturn($data);
        }
        $tb_data = array();
        $tb_data['fee']=$service_charge;//手续费
        $tb_data['currency_id']=$currency_id;
        $tb_data['user_id']=$member_id;
        $tb_data['url']=$cur_address;
        $tb_data['name']=$phone;
        $tb_data['num']=$total_num;
        $tb_data['actual']=number_format($actual,2,'.','');//实际到账价格
        $tb_data['status']=0;
        $tb_data['add_time']=time();
        if($total_num>$currency['currency_all_tibi']){ /*人工处理*/
            $tb_data["is_person_deal"] = 1;
            D("currency")->mem_dec_cur($currency_id,$total_num,$member_id);

            M("Tibi")->add($tb_data);
            $arr['info']="提币数量过大，请耐心等待人工处理";
            $this->ajaxReturn($arr);exit;
        }else{
            $tibi=$this->qianbao_tibi($cur_address,$actual,$currency);//提币程序
            if($tibi){//成功写入数据库
                $tb_data['ti_id']=$tibi;
                $re=M("Tibi")->add($tb_data);
                //减钱操作
                D("currency")->mem_dec_cur($currency_id,$total_num,$member_id);
                $arr['status']=1;
                $arr['info']="提币成功，请耐心等待";
                $this->ajaxReturn($arr);exit;

            }else{//失败提示
                $arr['status']=0;
                $arr['info']="提币失败";
                $this->ajaxReturn($arr);exit;
            }
        }
    }


    /*提现记录*/
    public function withdraw_record(){
        $db = M('tibi');
        $member_id = session('USER_KEY_ID');
        $list = $db->where(array(
            'user_id'=>$member_id,
            'status'=>array(
                'in',array(0,1,4)
            )

        ))->order("id desc")->select();
        $this->assign('list',$list);

        $this->display();
    }

    public function test(){
        $this->display();

    }

    /*二维码*/
    public function qrcodeimg(){

        Vendor('phpqrcode.phpqrcode');
        $QRcode = new \QRcode ();
        $errorCorrectionLevel = 'L';
        $matrixPointSize = 5;
        $type = I('type');
        $member_id = session('USER_KEY_ID');
        $phone = M('member')->where(array('member_id'=>$member_id))->getField('phone');
        $http = $_SERVER['HTTP_HOST'];
        $url = $type;
        $QRcode::png($url,false,$errorCorrectionLevel,$matrixPointSize);
    }

    /**
     * 提币引用的方法
     * @param unknown $url 钱包地址
     * @param unknown $money 提币数量
     *
     * 需要加密 *********************
     */
    private function qianbao_tibi($url,$money,$currency){
        require_once 'App/Common/Common/easybitcoin.php';
        $bitcoin = new \Bitcoin($currency['rpc_user'],$currency['rpc_pwd'],$currency['rpc_url'],$currency['port_number']);
//     	$result = $bitcoin->getinfo();
        $bitcoin->walletlock();//强制上锁
        $bitcoin->walletpassphrase($currency['qianbao_key'],20);
        $id=$bitcoin->sendtoaddress($url,$money);
        $bitcoin->walletlock();
        return $id;
    }


    /**
     * 查询某人的交易记录
     * @param unknown $user 用户名
     * @param unknown $count  从第几个开始查找
     * @return $list  返回此用户的交易列表
     */
    private function trade_qianbao($user,$currency){
        require_once 'App/Common/Common/easybitcoin.php';
        $bitcoin = new \Bitcoin($currency['rpc_user'],$currency['rpc_pwd'],$currency['rpc_url'],$currency['port_number']);
        $result = $bitcoin->getinfo();
        $list=$bitcoin->listtransactions($user,10,0);
        return $list;
    }



    public function rpc2(){
        require_once 'web/Common/Common/Common/easybitcoin.php';
        $bitcoin = new \Bitcoin('user','passwd','localhost','29991');
        $result = $bitcoin->getinfo();
        $id= $bitcoin->sendtoaddress('LXUVqocGoVivuEXd4SPquZC3W5eW7DVCMD',0.00001);

    }

    /**
     * 获取新的一个钱包地址
     * @return unknown
     */
    private function qianbao_new_address($currency){
        require_once 'App/Common/Common/easybitcoin.php';
        $bitcoin = new \Bitcoin($currency['rpc_user'],$currency['rpc_pwd'],$currency['rpc_url'],$currency['port_number']);
        $user=$_SESSION['USER_KEY'];
        $address = $bitcoin->getnewaddress($user);

        return $address;
    }
    /**
     * 检测地址是否是有效地址
     *
     * @return boolean 如果成功返回个true
     * @return boolean 如果失败返回个false；
     *  @param unknown $url
     *  @param $port_number 端口号 来区分不同的钱包
     */
    private function check_qianbao_address($url,$currency){

        require_once 'App/Common/Common/easybitcoin.php';
        $bitcoin = new \Bitcoin($currency['rpc_user'],$currency['rpc_pwd'],$currency['rpc_url'],$currency['port_number']);
        $address = $bitcoin->validateaddress($url);
        if($address['isvalid']){
            return true;
        }else{
            return false;
        }
    }


}
