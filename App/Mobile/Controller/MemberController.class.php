<?php
namespace Mobile\Controller;

class MemberController extends HomeController {
 	public function _initialize(){
 		parent::_initialize();
 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:404');
	}
	/*会员中心*/
    public function index(){

        $db = D('member');
        $member_id = session('USER_KEY_ID');
        $member_info = $db->get_info_by_id($member_id);

        /*是否签到*/
        $sign_where = array(
            'member_id' => $member_id,
            'type' => 1,
            'add_time' => array('between',array(strtotime(date("Y-m-d 00:00:00",time())),strtotime(date("Y-m-d 23:59:59",time()))))
        );
//       $sign_record = M("luckdraw_record")->where($sign_where)->find();
       $is_sign = 0;


        $this->assign('member_info',$member_info);
        $this->assign('is_sign',$is_sign);
	    $this->display();
    }
    /*实名认证*/
    public function cert(){
        $phone = session('USER_KEY');
        $member_id = session('USER_KEY_ID');

        if(IS_POST){
            $db = M('member_info');

            if($data = $db->create()){
                $data['member_id'] = $member_id;
                $data['create_time'] = time();
                $data['cert_num'] = $this->config['cert_num'];
                $data['is_cert'] = 1;

                $res = $db->add($data);
                if($res){
                    $data['status']= 1;
                    $data['info']="提交成功";
                    $this->ajaxReturn($data);
                }else{
                    $data['status']= 0;
                    $data['info']="提交失败";
                    $this->ajaxReturn($data);
                }
            }else{
                $data['status']= 0;
                $data['info']="未知错误";
                $this->ajaxReturn($data);
            }
        }else{
            /*银行列表*/
            $bank_list = M('bank')
                ->where(array('status'=>1))
                ->order('sort asc')
                ->select();

            $this->assign('bank_list',$bank_list);
            $this->assign('phone',$phone);
            $this->assign('default_bank_id',$bank_list?$bank_list[0]['id'] : "");
            $this->display();
        }
    }
    /*我的资料*/
    public function mem_info(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        if(IS_POST){

        }else{
            $member_info = $db->get_info_by_id($member_id);
            $member_info['bank_name'] = M('bank')->where(array('id'=>$member_info['bank_id']))->getField('bank_name');
            $this->assign('member_info',$member_info);
            $this->display();
        }
    }
    /*修改支付宝*/
    public function update_zfb(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        if(IS_POST){
            $save_data = array(
                'bank_no' => I('bank_no'),
                'zfb_no' => I('zfb_no')
            );
            $res = M('member_info')->where(array('member_id'=>$member_id))->save($save_data);
            if($res){
                $data['status']= 1;
                $data['info']="修改成功";
                $this->ajaxReturn($data);
            }else{
                $data['status']= 0;
                $data['info']="修改失败";
                $this->ajaxReturn($data);
            }
        }else{
            $member_info = $db->get_info_by_id($member_id);
            $member_info['bank_name'] = M('bank')->where(array('id'=>$member_info['bank_id']))->getField('bank_name');
            $this->assign('member_info',$member_info);
            $this->display();
        }
    }
    /*修改昵称*/
    public function update_nickname(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        if(IS_POST){
            $save_data = array(
                'nick_name' => I('nick_name'),
            );
            $res = M('member_info')->where(array('member_id'=>$member_id))->save($save_data);
            if($res){
                $data['status']= 1;
                $data['info']="修改成功";
                $this->ajaxReturn($data);
            }else{
                $data['status']= 0;
                $data['info']="修改失败";
                $this->ajaxReturn($data);
            }
        }else{
            $member_info = $db->get_info_by_id($member_id);
            $member_info['bank_name'] = M('bank')->where(array('id'=>$member_info['bank_id']))->getField('bank_name');
            $this->assign('member_info',$member_info);
            $this->display();
        }
    }
    /*修改登录密码*/
    public function update_passwd(){
        $member_id = session('USER_KEY_ID');
        $db = D('member');
        if(IS_POST){
            $repasswd = I('repasswd');
            $passwd = I('passwd');
            if($_POST['code']!= $_SESSION['code']){
                $data['status'] = 0;
                $data['info'] = '验证码错误';
                $this->ajaxReturn($data);
            }
            if($repasswd != $passwd){
                $data['status']= 0;
                $data['info']="两次密码不一致";
                $this->ajaxReturn($data);
            }

            $save_data = array(
                'pwd' => md5($passwd),
            );
            $res = $db->where(array('member_id'=>$member_id))->save($save_data);
            if($res){
                $data['status']= 1;
                $data['info']="修改成功";
                $this->ajaxReturn($data);
            }else{
                $data['status']= 0;
                $data['info']="修改失败";
                $this->ajaxReturn($data);
            }
        }else{
            $member_info = $db->get_info_by_id($member_id);
            $member_info['bank_name'] = M('bank')->where(array('id'=>$member_info['bank_id']))->getField('bank_name');
            $this->assign('member_info',$member_info);
            $this->display();
        }
    }

    /*修改地址*/
    public function update_address(){
        $member_id = session('USER_KEY_ID');
        $db = D('member_address');
        if(IS_POST){
            $receipt_name = I('receipt_name');
            $receipt_phone = I('receipt_phone');
            $receipt_address = I('receipt_address');

            $save_data = array(
                'receipt_name' =>$receipt_name,
                'receipt_phone' =>$receipt_phone,
                'receipt_address' => $receipt_address,
            );
            $is_exist = $db->where(array('member_id'=>$member_id))->find();
            if($is_exist){
                $res = $db->where(array('member_id'=>$member_id))->save($save_data);
            }else{
                $save_data['member_id'] =$member_id;
                $res = $db->add($save_data);
            }
            if($res){
                $data['status']= 1;
                $data['info']="修改成功";
                $this->ajaxReturn($data);
            }else{
                $data['status']= 0;
                $data['info']="修改失败";
                $this->ajaxReturn($data);
            }
        }else{
            $mem_address = $db->where(array('member_id'=>$member_id))->find();
            $this->assign('mem_address',$mem_address);
            $this->display();
        }
    }
    /*更改头像*/
    public function change_head(){
        $head_url = I('head_url');
        if(!$head_url){
            $data['status']= 0;
            $data['info']="请选择头像";
            $this->ajaxReturn($data);
        }
        $member_id = session('USER_KEY_ID');
        $r = M('member_info')->where(array('member_id'=>$member_id))->save(array('head_url'=>$head_url));
        if($r){
            $data['status']= 1;
            $data['info']="修改成功";
            $this->ajaxReturn($data);
        }else{
            $data['status']= 0;
            $data['info']="修改失败";
            $this->ajaxReturn($data);
        }
    }

    /*我的金币*/
    public function my_coin(){
        $this->display();
    }

    /*当前金币*/
    public function cur_coin(){
        $this->display();
    }








}