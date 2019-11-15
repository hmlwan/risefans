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

        /*用户金币数量*/
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');
        $rmb_num = 0;
        if($mem_cur_num > 0){
            $rmb_num = number_format($mem_cur_num/100,2,'.','');
        }
        $this->assign('rmb_num',$rmb_num);
        $this->assign('mem_cur_num',$mem_cur_num);
        $this->assign('member_info',$member_info);
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
                    $invite_record_db = M('invite_record');
                    /*实名奖励*/
                    $invite_conf = M('invite_conf')->where("1=1")->find();
                    $mem_info = D('member')->get_info_by_id($member_id);
                    /*父级*/
                    $fa_info1 = D('member')->where(array('unique_code'=>$mem_info['pid']))->find();
                    if($fa_info1){
                        $invite_record_data1 = array(
                            'member_id' => $fa_info1['member_id'],
                            'currency_id' => $invite_conf['f_currency_id_1'],
                            'num' => $invite_conf['f_currency_num_1'],
                            'sub_member_id' => $member_id,
                            'content' => "一级(".$member_id.")实名认证奖励".$invite_conf['f_currency_num_1'].'币',
                            'add_time' => time(),
                            'level' => 1,
                            'type' => 1,
                            'is_cert' => 2,
                        );
                        $r1 = $invite_record_db
                            ->where(array(
                                'member_id' => $fa_info1['member_id'],
                                'sub_member_id' => $member_id,
                                'type' => 1,
                                'is_cert'=>1
                                )
                            )
                            ->save($invite_record_data1);
                        if($r1){
                            /*加币种数量*/
                            M('currency_user')
                                ->where(array(
                                    'member_id'=>$fa_info1['member_id'],
                                    'currency_id'=>$invite_conf['f_currency_id_1'])
                                )
                                ->setInc('num',$invite_conf['f_currency_num_1']);
                            /*增加抽奖数*/
                            M("task_luckdraw_record")->add(array(
                                'member_id' => $fa_info1['member_id'],
                                'type' => 2,
                                'add_time' => time(),
                                'stype' => 1,
                                'use_luckdraw_num' => $invite_conf['reward_luckdraw_num'],
                            ));
                            if(M("member_luckdraw_num")->where(array('member_id'=>$fa_info1['member_id']))->find()){
                                M("member_luckdraw_num")
                                    ->where(array('member_id'=>$fa_info1['member_id']))
                                    ->setInc('invite_ld_num',$invite_conf['reward_luckdraw_num']);
                            }else{
                                M("member_luckdraw_num")
                                    ->add(array(
                                        'member_id'=>$fa_info1['member_id'],
                                        'invite_ld_num'=>$invite_conf['reward_luckdraw_num']
                                    )
                                );
                            }
                            /*统计*/
                            M('trade')->add(array(
                                'member_id' =>  $fa_info1['member_id'],
                                'currency_id' => $invite_conf['f_currency_id_1'],
                                'num' => $invite_conf['f_currency_num_1'],
                                'add_time' => time(),
                                'content' => '下线返利'.$invite_conf['f_currency_num_1'].'币',
                                'type' => 1,
                                'trade_type' => 3,
                            ));
                        }


                    }
                    $fa_info2 = D('member')->where(array('unique_code'=>$fa_info1['pid']))->find();
                    if($fa_info2){
                        $invite_record_data2 = array(
                            'member_id' => $fa_info2['member_id'],
                            'currency_id' => $invite_conf['f_currency_id_2'],
                            'num' => $invite_conf['f_currency_num_2'],
                            'sub_member_id' => $member_id,
                            'content' => "二级(".$member_id.")实名认证奖励".$invite_conf['f_currency_num_2'].'币',
                            'add_time' => time(),
                            'level' => 2,
                            'type' => 1,
                            'is_cert' => 2,
                        );
                        $r2 = $invite_record_db
                            ->where(array(
                                    'member_id' => $fa_info2['member_id'],
                                    'sub_member_id' => $member_id,
                                    'type' => 1,
                                    'is_cert'=>1
                                )
                            )
                            ->save($invite_record_data2);
                        if($r2){
                            /*加币种数量*/
                            M('currency_user')
                                ->where(array(
                                        'member_id'=>$fa_info2['member_id'],
                                        'currency_id'=>$invite_conf['f_currency_id_2'])
                                )
                                ->setInc('num',$invite_conf['f_currency_num_2']);
                            /*增加抽奖数*/
                            M("task_luckdraw_record")->add(array(
                                'member_id' => $fa_info2['member_id'],
                                'type' => 2,
                                'add_time' => time(),
                                'stype' => 1,
                                'use_luckdraw_num' => $invite_conf['reward_luckdraw_num'],
                            ));

                            if(M("member_luckdraw_num")->where(array('member_id'=>$fa_info2['member_id']))->find()){
                                M("member_luckdraw_num")
                                    ->where(array('member_id'=>$fa_info2['member_id']))
                                    ->setInc('invite_ld_num',$invite_conf['reward_luckdraw_num']);
                            }else{
                                M("member_luckdraw_num")
                                    ->add(array(
                                            'member_id'=>$fa_info2['member_id'],
                                            'invite_ld_num'=>$invite_conf['reward_luckdraw_num']
                                        )
                                    );
                            }
                            /*统计*/
                            M('trade')->add(array(
                                'member_id' =>  $fa_info2['member_id'],
                                'currency_id' => $invite_conf['f_currency_id_2'],
                                'num' => $invite_conf['f_currency_num_2'],
                                'add_time' => time(),
                                'content' => '下线返利'.$invite_conf['f_currency_num_2'].'币',
                                'type' => 1,
                                'trade_type' => 3,
                            ));
                        }
                    }
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
        $member_id = session('USER_KEY_ID');
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');
        /*今日金币*/
        $today_sum_num = M('task_luckdraw_record')
            ->where(array('member_id'=>$member_id,
                'currency_id'=>3,
                'stype' =>2,
                'add_time'=>array('between',array(strtotime(date('Y-m-d 0:0:0',time())),strtotime(date('Y-m-d 23:59:59',time()))))
                )
            )
            ->sum('num');
        /*累计收益*/
        $sum_num = M('task_luckdraw_record')
            ->where(array('member_id'=>$member_id,
                    'currency_id'=>3,
                    'stype' =>2,
                )
            )
            ->sum('num');
        $this->assign('today_sum_num',$today_sum_num);
        $this->assign('sum_num',$sum_num);
        $this->assign('mem_cur_num',$mem_cur_num);
        /*金币明细*/
        $list = M('trade')
            ->where(array('member_id'=>$member_id,'currency_id'=>3,'type' =>1))
            ->order("add_time desc")->select();
        $re_data = array();
        if($list){
            foreach ($list as $key => $value){
                $value['currency_name'] = M('currency')->where(
                    array('currency_id'=>$value['currency_id'])
                )->getField('currency_name');

                $date = date("Y-m-d",$value['add_time']);
                if($date == date("Y-m-d",time())){
                    $re_data['今日'][] = $value;
                }else{
                    $re_data[$date][] = $value;
                }
            }
        }
        $this->assign('re_data',$re_data);
        $this->display();
    }

    /*当前金币*/
    public function cur_coin(){
        $this->display();
    }








}