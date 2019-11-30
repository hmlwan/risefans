<?php
namespace Mobile\Controller;

class MemberController extends HomeController {
 	public function _initialize(){
 		parent::_initialize();
 	}
	//空操作
	public function _empty(){
		header("HTTP/1.0 404 Not Found");
		$this->display('Public:face_to_face');
	}
	/*会员中心*/
    public function index(){

        $db = D('member');
        $member_id = session('USER_KEY_ID');
        $member_info = $db->get_info_by_id($member_id);

        /*用户金币数量*/
        $mem_jb_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>3))->getField('num');
        $mem_jb_num = number_format($mem_jb_num,2,'.','');

        /*莱特币*/
        $mem_ltb_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>2))->getField('num');
        $mem_ltb_num = number_format($mem_ltb_num,4,'.','');

        $member_info['phone'] = substr_replace($member_info['phone'],"****",3,4);

        $this->assign('mem_ltb_num',$mem_ltb_num);
        $this->assign('mem_jb_num',$mem_jb_num);

        /*首页广告*/
        $my_ad_list = M('my_ad')->where(array('status'=>1))->order('id asc')->select();

        $my_ad_count = M('my_ad')->where(array('status'=>1))->count();

        /*用户阅读广告次数*/
        $mem_ad_num = M("my_ad_detail")->where(array('member_id'=>$member_id))->sum('watch_num');
        $get_num_k = $mem_ad_num + 1;

        if(($mem_ad_num / $my_ad_count )>= 1){
            $get_num_k = ($mem_ad_num%$my_ad_count) + 1;
        }

        $my_ad_info = $my_ad_list[$get_num_k-1];

        if($my_ad_info){
            $ad_detail_is_exist = M("my_ad_detail")
                ->where(array('member_id'=>$member_id,'ad_id'=>$my_ad_info['id']))
                ->find();
            if($ad_detail_is_exist){
                M("my_ad_detail")->where(array('member_id'=>$member_id,'ad_id'=>$my_ad_info['id']))->setInc('watch_num',1);
            }else{
                M("my_ad_detail")->add(array('member_id'=>$member_id,'ad_id'=>$my_ad_info['id'],'watch_num'=>1));
            }
        }
        $this->assign('member_info',$member_info);
        $this->assign('my_ad_info',$my_ad_info);

        /*随机数*/
        $this->assign('tx_no',rand(1,12));
        $this->display();
    }
    /*实名认证*/
    public function cert(){
        $phone = session('USER_KEY');
        $member_id = session('USER_KEY_ID');
        $db = D('member');

        if(IS_POST){
            $is_out =0;
            $bankcard = I('bank_no');
            $true_name = I('true_name');
            $phone = I('phone');
            $id_card = I('id_card');
            $member_info = $db->get_info_by_id($member_id);
            $cert_error_num = $member_info['cert_error_num'];
            if(!$bankcard || !$true_name || !$phone || !$id_card){
                $cert_error_num1 = $cert_error_num+1;
                M("member")->where(array('member_id'=>$member_id))->setInc('cert_error_num',1);
                if($cert_error_num1 > 5){
                    $is_out =1;
                    M("member")->where(array('member_id'=>$member_id))->save(array(
                        'is_lock' => 1,
                    ));
                }
                $data['status']= 2;
                $data['is_out']= $is_out ;;
                $data['info']="信息不符全";
                $this->ajaxReturn($data);
            }
            if($member_info['cert_error_num']>5){
                $cert_error_num2 = $cert_error_num+1;
                M("member")->where(array('member_id'=>$member_id))->setInc('cert_error_num',1);

                if($cert_error_num2 > 5){
                    M("member")->where(array('member_id'=>$member_id))->save(array(
                        'is_lock' => 1,
                    ));
                }
                $data['status']= 2;
                $data['is_out']= $is_out ;;
                $data['info']="信息不符全";
                $this->ajaxReturn($data);
            }
            $is_exist = M('member_info')->where(array(
                'member_id'=>array('neq',$member_id),
                '_string' => "bank_no={$bankcard} OR id_card={$id_card}"
            ))->find();
            if($is_exist){
                $cert_error_num3 = $cert_error_num+1;
                M("member")->where(array('member_id'=>$member_id))->setInc('cert_error_num',1);

                if($cert_error_num3 > 5){
                    M("member")->where(array('member_id'=>$member_id))->save(array(
                        'is_lock' => 1,
                    ));
                }
                $data['status']= 2;
                $data['is_out']= $is_out ;;
                $data['info']="已存在相同身份证号或银行卡号";
                $this->ajaxReturn($data);
            }

            $cert_res = $this->cert_api($bankcard,$id_card,$phone,$true_name);

            $rsp = json_decode($cert_res,true);

            if(!$cert_res || $rsp['code'] != 200){
                $cert_error_num4 = $cert_error_num+1;
                M("member")->where(array('member_id'=>$member_id))->setInc('cert_error_num',1);
                if($cert_error_num4 > 5){
                    M("member")->where(array('member_id'=>$member_id))->save(array(
                        'is_lock' => 1,
                    ));
                }
                $data['status']= 2;
                $data['is_out']= $is_out ;;
                $data['info'] = "信息不符实名失败";
                $this->ajaxReturn($data);
            }

            $db = M('member_info');

            if($data = $db->create()){
                $data['member_id'] = $member_id;
                $data['create_time'] = time();
                $data['cert_num'] = $this->config['cert_num'];
                $data['is_cert'] = 1;
                $tx_no = rand(1,12);
                $data['head_url'] = "/Public/Mobile/images/tx{$tx_no}.png";

                $res = $db->add($data);
                if($res){
                    $invite_record_db = M('invite_record');
                    /*实名奖励*/

                    $mem_info = D('member')->get_info_by_id($member_id);
                    /*父级*/
                    $fa_info1 = D('member')->where(array('phone'=>$mem_info['pid']))->find();
                    if($fa_info1){
                        $fa_info1_vip_level = D('member')->get_vip_level($fa_info1['member_id']);
                        $fa1_vip_level = M('vip_level_config')->where(array('type'=>$fa_info1_vip_level))->find();

                        if($fa1_vip_level){
                            $invite_record_data1 = array(
                                'member_id' => $fa_info1['member_id'],
                                'currency_id' => $fa1_vip_level['cert_cur_id']?$fa1_vip_level['cert_cur_id']:0,
                                'num' => $fa1_vip_level['cert_cur_num']?$fa1_vip_level['cert_cur_num']:0,
                                'sub_member_id' => $member_id,
                                'content' => "一级(".$member_id.")实名认证奖励".$fa1_vip_level['cert_cur_num'].'币',
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
                                            'currency_id'=>$fa1_vip_level['cert_cur_id'])
                                    )
                                    ->setInc('num',$fa1_vip_level['cert_cur_num']);

                                /*统计*/
                                $f1_balance = D('currency')->mem_cur_num($fa1_vip_level['cert_cur_id'],$fa_info1['member_id']);

                                M('trade')->add(array(
                                    'member_id' =>  $fa_info1['member_id'],
                                    'currency_id' => $fa1_vip_level['cert_cur_id'],
                                    'num' => $fa1_vip_level['cert_cur_num'],
                                    'add_time' => time(),
                                    'content' => '一级实名认证奖励'.$fa1_vip_level['cert_cur_num'].'币',
                                    'type' => 1,
                                    'trade_type' => 13,
                                    'balance' => $f1_balance,
                                    'oldbalance' => $f1_balance + $fa1_vip_level['cert_cur_num'],

                                ));
                            }
                        }
                    }
                    $fa_info2 = D('member')->where(array('phone'=>$fa_info1['pid']))->find();
                    if($fa_info2){
                        $fa_info2_vip_level = D('member')->get_vip_level($fa_info2['member_id']);
                        $fa2_vip_level = M('vip_level_config')->where(array('type'=>$fa_info2_vip_level))->find();
                        if($fa2_vip_level){
                            $invite_record_data2 = array(
                                'member_id' => $fa_info2['member_id'],
                                'currency_id' => $fa2_vip_level['cert_cur_id'],
                                'num' => $fa2_vip_level['cert_cur_num'],
                                'sub_member_id' => $member_id,
                                'content' => "二级(".$member_id.")实名认证奖励".$fa2_vip_level['cert_cur_num'].'币',
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
                                            'currency_id'=>$fa2_vip_level['cert_cur_id'])
                                    )
                                    ->setInc('num',$fa2_vip_level['cert_cur_num']);
                                /*统计*/
                                $f2_balance = D('currency')->mem_cur_num($fa2_vip_level['cert_cur_id'],$fa_info2['member_id']);

                                M('trade')->add(array(
                                    'member_id' =>  $fa_info2['member_id'],
                                    'currency_id' => $fa2_vip_level['cert_cur_id'],
                                    'num' => $fa2_vip_level['cert_cur_num'],
                                    'add_time' => time(),
                                    'content' => '二级实名认证奖励'.$fa2_vip_level['cert_cur_num'].'币',
                                    'type' => 1,
                                    'trade_type' => 13,
                                    'balance' => $f2_balance,
                                    'oldbalance' => $f2_balance + $fa2_vip_level['cert_cur_num'],

                                ));
                            }
                        }
                    }
                    $data['status']= 1;
                    $data['info']="实名成功";
                    $this->ajaxReturn($data);
                }else{
                    $data['status']= 0;
                    $data['info']="信息不符实名失败";
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
            if($_POST['yzm']!= $_SESSION['code']){
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
        $today_sum_num = M('trade')
            ->where(array('member_id'=>$member_id,
                'currency_id'=>3,
                'type' =>1,
                'add_time'=>array('between',array(strtotime(date('Y-m-d 0:0:0',time())),strtotime(date('Y-m-d 23:59:59',time()))))
                )
            )
            ->sum('num');
        /*累计收益*/
        $sum_num = M('trade')
            ->where(array('member_id'=>$member_id,
                    'currency_id'=>3,
                    'type' =>1,
                )
            )
            ->sum('num');
        $today_sum_num =  number_format($today_sum_num,0,'','');
        $sum_num =  number_format($sum_num,0,'','');
        $mem_cur_num =  number_format($mem_cur_num,0,'','');
        $this->assign('today_sum_num',$today_sum_num);
        $this->assign('sum_num',$sum_num);
        $this->assign('mem_cur_num',$mem_cur_num);
        /*金币明细*/
        $list = M('trade')
            ->where(array('member_id'=>$member_id,'currency_id'=>3))
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
//        dd($re_data);
        $this->assign('re_data',$re_data);
        /*1莱特币等于多少金币*/
        $jb_ltc_rate = $this->config['jb_ltc_rate'];

        $jb_ltc_rate_num = (1/$jb_ltc_rate)?(1/$jb_ltc_rate):100;
        $jb_ltc_rate_num = number_format($jb_ltc_rate_num,0,'','');
        $this->assign('jb_ltc_rate_num',$jb_ltc_rate_num);
        $this->display();
    }
    /*当前莱特币*/
    public function cur_coin(){
        $member_id = session('USER_KEY_ID');
        $mem_cur_num = M('currency_user')->where(array('member_id'=>$member_id,'currency_id'=>2))->getField('num');

        /*累计充值*/
        $recharge_sum_num = M('rechage_record')
            ->where(array('member_id'=>$member_id,
                    'currency_id'=>2,
                    'status' =>1,
                )
            )
            ->sum('num');
        /*累计提现*/
        $tixian_sum_num = M('withdraw_record')
            ->where(array('member_id'=>$member_id,
                    'currency_id'=>2,
                    'type' =>1,
                )
            )
            ->sum('num');
        $recharge_sum_num =  number_format($recharge_sum_num,0,'','');
        $tixian_sum_num =  number_format($tixian_sum_num,0,'','');
        $mem_cur_num =  number_format($mem_cur_num,0,'','');
        $this->assign('recharge_sum_num',$recharge_sum_num);
        $this->assign('tixian_sum_num',$tixian_sum_num);
        $this->assign('mem_cur_num',$mem_cur_num);

        /*莱特币明细*/
        $list = M('trade')
            ->where(array('member_id'=>$member_id,'currency_id'=>2))
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

        /*1莱特币等于多少金币*/
        $jb_ltc_rate = $this->config['jb_ltc_rate'];

        $jb_ltc_rate_num = (1/$jb_ltc_rate)?(1/$jb_ltc_rate):100;
        $jb_ltc_rate_num = number_format($jb_ltc_rate_num,0,'','');
        $this->assign('jb_ltc_rate_num',$jb_ltc_rate_num);
        $this->display();
    }








}