<?php
namespace Mobile\Controller;
use Common\Controller\CommonController;
class HomeController extends CommonController {
 	protected $member;
 	protected $trade;
 	protected $auth;
	public function _initialize(){
 		parent::_initialize();

        if(IS_POST){
            if(empty($_SESSION['USER_KEY_ID'])){
                $data['status'] = 2;
                $data['info'] = '请先登陆';
                $this->ajaxReturn($data);
            }
        }

  		if(empty($_SESSION['USER_KEY_ID'])){
  			$this->redirect("Login/index");

  		}

  		 // 添加用户真实姓名等
  		$this->auth = M('Member')->where('member_id ='.$_SESSION['USER_KEY_ID'])->find();
  		if (empty($this->auth)){
  		    $this->redirect("Login/index");
  		}
  		$this->assign('auth',$this->auth);
 		
 		//修正会员各个币种信息  currency_user
 		$currency=M('Currency')->select();
 		/*ip不一致重新登录*/
 		$session_id = session_id();
 		$mem_info =  M('member')->where(array('member_id'=>$_SESSION['USER_KEY_ID']))->field('session_id,is_lock')->find();
        if(($mem_info['session_id'] != $session_id )|| $mem_info['is_lock'] == 1){
            $_SESSION['USER_KEY_ID']=null;
            $_SESSION['USER_KEY']=null;
            $_SESSION['STATUS']=null;
            $_SESSION['transaction_again']=null;
            $this->redirect('Login/index');
        }
	}
    public function get_member_id(){
        return $_SESSION['USER_KEY_ID'];
    }
	/**
	 * 添加currency_user表方法
	 * @param int $uid 会员id
	 * @param int $cid 币种id
	 */
	 public function addCurrencyUser($uid,$cid){
	 	$data['member_id']=$uid;
	 	$data['currency_id']=$cid;
	 	$data['num']=0;
	 	$data['forzen_num']=0;
	 	$data['status']=0;
	 	$rs=M('Currency_user')->add($data);
	 	if($rs){
	 		return true;
	 	}else{
	 		return false;
	 	}
	 }
	//获取会员有多少人工充值订单
	public function getPaycountByName($name){
		$list=M('Pay')->where("member_name='".$name."'")->count();
		if($list){
			return $list;
		}else{
			return false;
		}
	}
	
	//获取个人账户指定币种金额
	public function getUserMoneyByCurrencyId($user,$currencyId){
	    return M('Currency_user')->field('num,forzen_num,chongzhi_url')->where("Member_id={$this->member['member_id']} and currency_id=$currencyId")->find();
	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	
	
	/**
	 * 解冻程序
	 * 现方法为根据众筹设置的解冻比例解冻
	 * Ps:注释掉部分为 默认配置项一个解冻比例
	 * @return boolean
	 */
	private function jiedong(){
		$time=time();
// 		$bili=$this->config['jiedong_bili']/100;
		$bili = null;
		
		$list=M('Issue_log')->where("deal>0 and add_time<{$time}-60*60*24 and uid={$_SESSION['USER_KEY_ID']} and status=0")->select();
		if(!$list){
			return false;
		}
		foreach ($list as $k=>$v){
			$v['remove_forzen_bili'] = $this->getIssueRemoveForzenBiLiByIssueId($v['id'])/100;
			M('Issue_log')->where("id={$v['id']}")->setDec('deal',$v['num']*$v['remove_forzen_bili']);
			M('Issue_log')->where("id={$v['id']}")->setField('add_time',time());
			M('Currency_user')->where("member_id={$v['uid']} and currency_id={$v['cid']}")->setInc('num',$v['num']*$v['remove_forzen_bili']);
			M('Currency_user')->where("member_id={$v['uid']} and currency_id={$v['cid']}")->setDec('forzen_num',$v['num']*$v['remove_forzen_bili']);
			if($v['deal']==0){
				M('Issue_log')->where("id={$v['id']}")->setField('status',1);
			}
// 			M('Issue_log')->where("id={$v['id']}")->setDec('deal',$v['num']*$bili);
// 			M('Issue_log')->where("id={$v['id']}")->setField('add_time',time());
// 			M('Currency_user')->where("member_id={$v['uid']} and currency_id={$v['cid']}")->setInc('num',$v['num']*$bili);
// 			M('Currency_user')->where("member_id={$v['uid']} and currency_id={$v['cid']}")->setDec('forzen_num',$v['num']*$bili);
// 			if($v['deal']==0){
// 				M('Issue_log')->where("id={$v['id']}")->setField('status',1);			
// 			}
		}
	}
	/**
	 * 根据认筹id查找解冻比例
	 * @param int $id Issue Id
	 * @return 解冻比例
	 */
	private function getIssueRemoveForzenBiLiByIssueId($id){
		$list =  M('Issue')->field('is_forzen,remove_forzen_bili')->where("id = $id")->find();
		if($list['is_forzen']==0){
			return $list['remove_forzen_bili'];
		}else{
			return 0;
		}
	}
	
	//图片处理
	public function upload($file){
	    
	    switch($file['type'])
	    {
	        case 'image/jpeg': $ext = 'jpg'; break;
	        case 'image/gif': $ext = 'gif'; break;
	        case 'image/png': $ext = 'png'; break;
	        case 'image/tiff': $ext = 'tif'; break;
	        default: $ext = ''; break;
	    }
	    if (empty($ext)){
	        return false;
	    }
		$upload = new \Think\Upload();// 实例化上传类
		$upload->maxSize   =     3145728 ;// 设置附件上传大小
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
		$upload->savePath  =      './Public/Uploads/'; // 设置附件上传目录
		// 上传文件
		$info   =  $upload->uploadOne($file);
		if(!$info) {
			// 上传错误提示错误信息
			$this->error($upload->getError());exit();
		}else{
			// 上传成功
			$pic=$info['savepath'].$info['savename'];
			$url='/Uploads'.ltrim($pic,".");
		}
		return $url;
	}

}