<?php
namespace Mobile\Controller;

use Common\Controller\CommonController;
class RegController extends CommonController {

    /**
     * 显示注册界面
     */
    public function index(){
        $pid = I('get.Member_id','','intval');
        $this->assign('pid',$pid);
        $this->display();
    }
    /* 验证码生成 */
    public function verifys(){

        $config =   array(
            'fontSize'  =>  10,              // 验证码字体大小(px)
            'useCurve'  =>  true,            // 是否画混淆曲线
            'useNoise'  =>  true,            // 是否添加杂点
            'imageH'    =>  35,               // 验证码图片高度
            'imageW'    =>  80,               // 验证码图片宽度
            'length'    =>  4,               // 验证码位数
            'fontttf'   =>  '4.ttf',              // 验证码字体，不设置随机获取
        );
        $verify=new \Think\Verify($config);
        $verify->entry();
    }
    /**
     * 添加注册用户
     */
    public function addReg(){

        if(IS_POST){
            //增加添加时间,IP
            $_POST['reg_time'] = time();
            $_POST['ip'] = get_client_ip();
            $_POST['status'] = 1;
            $_POST['is_lock'] = 0;
            $_POST['code'] = 1234;
            $_SESSION['code'] = 1234;
            if($_POST['code']!= $_SESSION['code']){
                $data['status'] = 0;
                $data['info'] = '验证码错误';
                $this->ajaxReturn($data);
            }
            $pid =   $_POST['pid'];
            $M_member = D('Member');
            $info = M('Member')->where(array('phone'=>$_POST['phone']))->find();
            if($info){
                $data['status'] = 2;
                $data['info'] = "手机号码已经存在";
                $this->ajaxReturn($data);
            }
            if($pid){
               $p_info =  M('Member')->where(array('phone'=>$pid))->find();
               if(!$p_info){
                   $data['status'] = 2;
                   $data['info'] = "邀请码不存在";
                   $this->ajaxReturn($data);
               }
            }
            if (!$M_member->create()){
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $data['status'] = 0;
                $data['info'] = $M_member->getError();
                $this->ajaxReturn($data);
                return;
            }else{
                $r = $M_member->add();
                if($r){
                    $last_mem = M('Member')->order('member_id desc')->limit(1)->find();
                    if($last_mem){
                         $unique_code = $last_mem['unique_code'] + rand(1, 20);
                    }else{
                         $unique_code = $this->config['init_recomment_code'];
                     }
                    $M_member->where(array('member_id'=>$r))->save(array('unique_code'=>$unique_code));

                    /*添加用户币种*/
                    $cur_list = M('currency')->where(array('is_lock'=>0))->select();
                    $user_cur_data = array(
                        'member_id'=>$r,
                        'num' => 0,
                        'forzen_num' => 0,
                        'status' => 1,
                    );
                    foreach ($cur_list as $c_value){
                        $user_cur_data['currency_id'] = $c_value['currency_id'];
                        M('currency_user')->add($user_cur_data);
                    }
                    /*添加邀请信息*/
                    if($pid){
                        M('invite_record')->add(array(
                                'member_id' => $p_info['member_id'],
                                'sub_member_id' => $r,
                                'add_time' => time(),
                                'level' => 1,
                                'type' => 1,
                                'is_cert' => 1,
                            )
                        );
                        $pp_info =  M('Member')->where(array('phone'=>$p_info['pid']))->find();
                        if($pp_info){
                            M('invite_record')->add(array(
                                    'member_id' => $pp_info['member_id'],
                                    'sub_member_id' => $r,
                                    'add_time' => time(),
                                    'level' => 2,
                                    'type' => 1,
                                    'is_cert' => 1,
                                )
                            );
                        }
                    }
                    $data['status'] = 1;
                    $data['info'] = '注册成功，请去登录';
                    $this->ajaxReturn($data);
                }else{
                    $data['status'] = 0;
                    $data['info'] = '服务器繁忙,请稍后重试';
                    $this->ajaxReturn($data);
                }
            }
        }
    }
    /*step2: 发送验证码*/
    public function get_code(){
        $mobile = I('get.phone','');
        if(!$mobile){
           $this->redirect('Reg/reg');
        }
        $this->assign('phone',$mobile);
        $this->display();
    }
    /*注册成功操作*/
    public function op_reg()
    {
        if(IS_POST){
            $phone = I('phone');
            $procedure = $_SESSION['procedure'];
            if($procedure != 1){
                $data['status'] = 2;
                $data['info'] = '请先去输入手机号码';
                $this->ajaxReturn($data);
            }
            if($_POST['code']!=$_SESSION['code']){
                $data['status'] = 0;
                $data['info'] = '验证码错误';
                $this->ajaxReturn($data);
            }
            $res = M('Member')->where(array('phone'=> $phone))->find();
            if(!$res){
                $data['status'] = 2;
                $data['info'] = '请先去输入手机号码';
                $this->ajaxReturn($data);
            }
            $save_res = M('Member')->where(array('phone'=> $phone))->save(array('status'=>1));
            if(false !== $save_res){
                $data['status'] = 1;


                $data['info'] = '注册成功';
                $this->ajaxReturn($data);
            }else{
                $data['status'] = 0;
                $data['info'] = '注册失败';
                $this->ajaxReturn($data);
            }
        }

    }
    /**
     * 添加个人信息
     */
    public function modify(){
        //判断是否是已经完成reg基本注册
       $login = $this->checkLogin();
       if(!$login){
      	 	$this->redirect('User/index');
       		return;
       }
       if(session('STATUS')!=0){
            $this->redirect('User/index');
            return;
        }
        if(IS_POST){
            $M_member = D('Member');
            $id = session('USER_KEY_ID');
            $_POST['member_id']=$id;
            $_POST['status'] = 1;        //0=有效但未填写个人信息1=有效并且填写完个人信息2=禁用
            if (!$data=$M_member->create()){ // 创建数据对象
                // 如果创建失败 表示验证没有通过 输出错误提示信息
                $data['status'] = 0;
                $data['info'] = $M_member->getError();
                $this->ajaxReturn($data);
//                $this->error($M_member->getError());
                return;
            }else {
                $where['member_id'] = $id;
                $r = $M_member->where($where)->save();
                if($r){
                    session('procedure',2);//SESSION 跟踪第二步
                    session('STATUS',1);
                    $data['status'] = 1;
                    $data['info'] = "提交成功";
                    $this->ajaxReturn($data);
//                    $this->redirect('Reg/regSuccess');
                }else{
                    $data['status'] = 0;
                    $data['info'] = '服务器繁忙,请稍后重试';
                    $this->ajaxReturn($data);
//                    $this->error('服务器繁忙,请稍后重试');
//                    return;
                }
            }
        }else{
            $this->display();
        }
    }
    /**
     * 注册成功
     */
    public function regSuccess(){
		 $this->display();
/*
        if(session('USER_KEY_ID')){
            $this->redirect('User/regSuccess');
            return;
        }
        //判断步骤并重置
        if(session('procedure')==2){
            session('procedure',null);
            $this->display();
        }
        if(session('procedure')==1){
            $this->redirect('Reg/reg');
        }
*/
    }

    /**
     * ajax验证邮箱
     * @param string $email 规定传参数的结构
     * 
     */
    public function ajaxCheckEmail($email){
        $email = urldecode($email);
        $data = array();
        if(!checkEmail($email)){
            $data['status'] = 0;
            $data['msg'] = "邮箱格式错误";
        }else{
            $M_member = M('Member');
            $where['email']  = $email;
            $r = $M_member->where($where)->find();
            if($r){
                $data['status'] = 0;
                $data['msg'] = "邮箱已存在";
            }else{
                $data['status'] = 1;
                $data['msg'] = "";
            }
        }
        $this->ajaxReturn($data);
    }
}